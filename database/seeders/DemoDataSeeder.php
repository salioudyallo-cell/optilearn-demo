<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Group;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\Organization;
use App\Models\Review;
use App\Models\User;
use App\Services\CourseAssigner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Jeu de données de démonstration RICHE, couvrant les deux modes (Commercial et
 * Entreprise), pour une instance de présentation aux prospects. Sans factory (Faker
 * absent en production) et idempotent : relançable sans doublon.
 *
 *     php artisan db:seed --class=DemoDataSeeder --force
 *
 * N'envoie aucun e-mail (création directe des certificats).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::firstOrCreate(
            ['email' => 'formateur@opti-leads.com'],
            [
                'name' => 'Fatou Ndiaye',
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::Instructor,
                'company' => 'OptiLeads',
                'country' => 'SN',
                'email_verified_at' => now(),
            ],
        );

        // Catalogue (3 formations + miniatures + codes) via le seeder dédié.
        $this->call(CatalogDemoSeeder::class);

        $courses = Course::query()->whereIn('slug', [
            'demo-plateforme-video',
            'generer-des-leads-b2b-linkedin-emailing',
            'piloter-acquisition-par-la-donnee',
        ])->get()->keyBy('slug');

        $learners = $this->seedLearners();
        $this->seedReviews($courses, $learners);
        $this->seedEnterprise($courses, $learners);
        $this->seedOrders($courses->get('generer-des-leads-b2b-linkedin-emailing'), $learners);

        $this->command->info('Jeu de démonstration prêt : '.$learners->count().' apprenants, 1 organisation, avis, commandes.');
    }

    /**
     * @return Collection<int, User>
     */
    private function seedLearners(): Collection
    {
        $names = [
            'Awa Diop', 'Moussa Traoré', 'Aminata Sow', 'Ibrahima Fall', 'Mariama Bâ',
            'Cheikh Gueye', 'Khady Sarr', 'Ousmane Diallo', 'Ndeye Faye', 'Modou Kane',
        ];

        return collect($names)->map(function (string $name, int $i): User {
            $slug = Str::slug($name);

            return User::firstOrCreate(
                ['email' => "{$slug}@demo.opti-leads.com"],
                [
                    'name' => $name,
                    'password' => Hash::make('password'),
                    'role' => UserRole::Learner,
                    'country' => ['SN', 'CI', 'ML'][$i % 3],
                    'email_verified_at' => now(),
                ],
            );
        });
    }

    /**
     * @param  Collection<string, Course>  $courses
     * @param  Collection<int, User>  $learners
     */
    private function seedReviews(Collection $courses, Collection $learners): void
    {
        $reviews = [
            [5, 'Formation très concrète, directement applicable à mon activité.'],
            [4, 'Bon contenu, quelques passages un peu rapides mais l’essentiel est là.'],
            [5, 'Les exemples adaptés au marché local font toute la différence.'],
            [4, 'Claire et bien structurée, je recommande.'],
            [3, 'Correct, j’aurais aimé plus d’études de cas.'],
        ];

        foreach ($courses as $course) {
            $learners->take(5)->values()->each(function (User $learner, int $i) use ($course, $reviews): void {
                [$rating, $comment] = $reviews[$i];
                Review::updateOrCreate(
                    ['user_id' => $learner->getKey(), 'course_id' => $course->getKey()],
                    ['rating' => $rating, 'comment' => $comment],
                );
            });
        }
    }

    /**
     * @param  Collection<string, Course>  $courses
     * @param  Collection<int, User>  $learners
     */
    private function seedEnterprise(Collection $courses, Collection $learners): void
    {
        $org = Organization::updateOrCreate(
            ['slug' => 'pme-senegal-sarl'],
            ['name' => 'PME Sénégal SARL', 'contact_email' => 'rh@pme-senegal.sn', 'is_active' => true],
        );

        // Rattache les apprenants à l'organisation.
        User::whereIn('id', $learners->pluck('id'))->update(['organization_id' => $org->getKey()]);

        $marketing = Group::updateOrCreate(['organization_id' => $org->getKey(), 'name' => 'Équipe marketing']);
        $commercial = Group::updateOrCreate(['organization_id' => $org->getKey(), 'name' => 'Équipe commerciale']);

        $marketing->members()->syncWithoutDetaching($learners->take(5)->pluck('id'));
        $commercial->members()->syncWithoutDetaching($learners->slice(5)->pluck('id'));

        $assigner = app(CourseAssigner::class);
        $assigner->assignCourseToGroup($courses->get('generer-des-leads-b2b-linkedin-emailing'), $marketing);
        $assigner->assignCourseToGroup($courses->get('piloter-acquisition-par-la-donnee'), $commercial);

        // Progression réaliste : certains membres avancent, sous 7 jours (KPI « actifs »).
        $this->seedProgress($courses, $learners);
    }

    /**
     * @param  Collection<string, Course>  $courses
     * @param  Collection<int, User>  $learners
     */
    private function seedProgress(Collection $courses, Collection $learners): void
    {
        $course = $courses->get('demo-plateforme-video');
        $lessons = $course->modules()->with('lessons')->get()->pluck('lessons')->flatten();

        // 3 apprenants terminent la formation courte -> certificat.
        $learners->take(3)->each(function (User $learner) use ($course, $lessons): void {
            foreach ($lessons as $lesson) {
                LessonProgress::updateOrCreate(
                    ['user_id' => $learner->getKey(), 'lesson_id' => $lesson->getKey()],
                    ['completed_at' => now()->subDays(random_int(0, 5))],
                );
            }

            Certificate::firstOrCreate(
                ['user_id' => $learner->getKey(), 'course_id' => $course->getKey()],
                [
                    'serial' => sprintf('OPT-%d-%s', now()->year, strtoupper(Str::random(6))),
                    'issued_at' => now()->subDays(random_int(0, 4)),
                ],
            );
        });

        // 4 autres sont en cours (1 leçon terminée récemment).
        $learners->slice(3, 4)->each(function (User $learner) use ($lessons): void {
            $first = $lessons->first();
            if ($first !== null) {
                LessonProgress::updateOrCreate(
                    ['user_id' => $learner->getKey(), 'lesson_id' => $first->getKey()],
                    ['completed_at' => now()->subDays(random_int(0, 3))],
                );
            }
        });
    }

    /**
     * @param  Collection<int, User>  $learners
     */
    private function seedOrders(?Course $course, Collection $learners): void
    {
        if ($course === null) {
            return;
        }

        Order::firstOrCreate(
            ['provider_ref' => 'DEMOPAID0001'],
            [
                'user_id' => $learners->first()->getKey(),
                'course_id' => $course->getKey(),
                'amount_fcfa' => $course->price_fcfa,
                'provider' => 'offline',
                'status' => OrderStatus::Paid,
                'paid_at' => now()->subDays(2),
            ],
        );

        Order::firstOrCreate(
            ['provider_ref' => 'DEMOPEND0002'],
            [
                'user_id' => $learners->get(1)->getKey(),
                'course_id' => $course->getKey(),
                'amount_fcfa' => $course->price_fcfa,
                'provider' => 'offline',
                'status' => OrderStatus::Pending,
            ],
        );
    }
}
