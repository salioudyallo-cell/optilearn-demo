<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Models\AccessCode;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Jeu de demonstration pour le developpement et les tests manuels.
 * Ce n'est PAS du contenu de production : la premiere formation reelle sera creee
 * via Filament, jamais par un seeder.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Administrateur OptiLeads',
            'email' => 'admin@opti-leads.com',
            'password' => Hash::make('password'),
            'company' => 'OptiLeads',
            'country' => 'SN',
        ]);

        $instructor = User::factory()->instructor()->create([
            'name' => 'Formateur OptiLeads',
            'email' => 'formateur@opti-leads.com',
            'password' => Hash::make('password'),
            'company' => 'OptiLeads',
            'country' => 'SN',
        ]);

        $enrolledLearner = User::factory()->learner()->create([
            'name' => 'Awa Diop',
            'email' => 'awa@example.com',
            'password' => Hash::make('password'),
            'company' => 'Sonatel',
            'country' => 'SN',
        ]);

        User::factory()->learner()->create([
            'name' => 'Moussa Traoré',
            'email' => 'moussa@example.com',
            'password' => Hash::make('password'),
            'company' => 'Orange Mali',
            'country' => 'ML',
        ]);

        $course = Course::factory()->published()->create([
            'slug' => 'seo-pour-generer-des-leads-b2b',
            'title' => 'SEO : générer des leads B2B en Afrique de l’Ouest',
            'subtitle' => 'Positionner votre site sur les requêtes qui déclenchent une demande de devis.',
            'description' => "## À qui s’adresse cette formation\n\n"
                .'Aux responsables marketing et dirigeants de PME au Sénégal, en Côte d’Ivoire et au Mali '
                ."qui veulent un flux régulier de demandes entrantes.\n\n"
                ."## Ce que vous saurez faire\n\n"
                ."- Identifier les requêtes à intention commerciale sur votre marché\n"
                ."- Structurer un site qui convertit la visite en demande de devis\n"
                .'- Mesurer le coût par lead réel de votre canal organique',
            'level' => CourseLevel::Intermediaire,
            'price_fcfa' => 150000,
            'instructor_id' => $instructor->id,
            'duration_minutes' => 240,
        ]);

        $module1 = Module::factory()->create([
            'course_id' => $course->id,
            'position' => 1,
            'title' => 'Comprendre l’intention de recherche B2B',
        ]);

        $module2 = Module::factory()->create([
            'course_id' => $course->id,
            'position' => 2,
            'title' => 'Construire les pages qui convertissent',
        ]);

        // Leçon 1 en accès libre : c'est la vitrine du cours sur la fiche publique.
        Lesson::factory()->preview()->create([
            'module_id' => $module1->id,
            'position' => 1,
            'title' => 'Pourquoi le SEO B2B diffère du SEO grand public',
            'type' => LessonType::Video,
            'bunny_video_id' => 'demo-video-0001',
            'duration_seconds' => 480,
        ]);

        Lesson::factory()->create([
            'module_id' => $module1->id,
            'position' => 2,
            'title' => 'Cartographier les requêtes à intention commerciale',
            'type' => LessonType::Video,
            'bunny_video_id' => 'demo-video-0002',
            'duration_seconds' => 720,
        ]);

        Lesson::factory()->text()->create([
            'module_id' => $module1->id,
            'position' => 3,
            'title' => 'Méthode : l’audit de mots-clés en 6 étapes',
            'content' => "## Les 6 étapes\n\n"
                ."1. Lister les problèmes que votre offre résout\n"
                ."2. Traduire chaque problème en formulation client\n"
                ."3. Vérifier le volume réel sur votre pays\n"
                ."4. Qualifier l’intention : information ou achat\n"
                ."5. Estimer la difficulté concurrentielle\n"
                ."6. Arbitrer selon votre capacité de production\n\n"
                .'> Un mot-clé à fort volume sans intention d’achat coûte plus qu’il ne rapporte.',
        ]);

        Lesson::factory()->pdf()->create([
            'module_id' => $module2->id,
            'position' => 1,
            'title' => 'Modèle de page service à télécharger',
            'asset_path' => 'courses/demo/modele-page-service.pdf',
        ]);

        $quizLesson = Lesson::factory()->quiz()->create([
            'module_id' => $module2->id,
            'position' => 2,
            'title' => 'Quiz : valider vos acquis',
        ]);

        $quiz = Quiz::factory()->create([
            'lesson_id' => $quizLesson->id,
            'pass_score_pct' => 70,
            'max_attempts' => 3,
        ]);

        $this->seedQuestion(
            $quiz,
            1,
            'Quelle requête traduit la plus forte intention d’achat ?',
            'Une requête qui contient une ville et un service exprime un besoin immédiat et local.',
            [
                ['label' => 'agence seo dakar tarif', 'is_correct' => true],
                ['label' => 'qu’est-ce que le référencement', 'is_correct' => false],
                ['label' => 'histoire des moteurs de recherche', 'is_correct' => false],
                ['label' => 'définition du marketing digital', 'is_correct' => false],
            ]
        );

        $this->seedQuestion(
            $quiz,
            2,
            'Quel indicateur mesure le mieux la performance commerciale du canal organique ?',
            'Le coût par lead qualifié rapporte le trafic à la valeur réellement générée.',
            [
                ['label' => 'Le coût par lead qualifié', 'is_correct' => true],
                ['label' => 'Le nombre de pages indexées', 'is_correct' => false],
                ['label' => 'Le nombre de mots par article', 'is_correct' => false],
                ['label' => 'La fréquence de publication', 'is_correct' => false],
            ]
        );

        $this->seedQuestion(
            $quiz,
            3,
            'Sur un marché où 75 % du trafic est mobile, quelle priorité technique s’impose ?',
            'Le temps de chargement sur réseau instable conditionne tout le reste.',
            [
                ['label' => 'Réduire le poids des pages et le JavaScript', 'is_correct' => true],
                ['label' => 'Multiplier les animations', 'is_correct' => false],
                ['label' => 'Ajouter un carrousel en page d’accueil', 'is_correct' => false],
                ['label' => 'Augmenter la résolution des images', 'is_correct' => false],
            ]
        );

        $this->seedQuestion(
            $quiz,
            4,
            'Que doit contenir en priorité une page de service destinée à convertir ?',
            'La preuve et l’appel à l’action priment sur la longueur du texte.',
            [
                ['label' => 'Une preuve concrète et un moyen de contact immédiat', 'is_correct' => true],
                ['label' => 'La biographie complète du dirigeant', 'is_correct' => false],
                ['label' => 'Un maximum de mots-clés répétés', 'is_correct' => false],
                ['label' => 'Les conditions générales de vente', 'is_correct' => false],
            ]
        );

        AccessCode::factory()->create([
            'code' => 'OPT7K4M9XQ',
            'course_id' => $course->id,
            'max_uses' => 5,
            'used_count' => 0,
            'label' => 'Cohorte SEO juillet — Sonatel',
            'created_by' => $admin->id,
        ]);

        AccessCode::factory()->create([
            'code' => 'OPT3H8N2VC',
            'course_id' => $course->id,
            'max_uses' => 1,
            'used_count' => 0,
            'label' => 'Accès individuel — prospect Abidjan',
            'created_by' => $admin->id,
        ]);

        $this->command->info(sprintf(
            'Démo créée : cours « %s » (%s), apprenant de test %s / password.',
            $course->title,
            CourseStatus::Published->value,
            $enrolledLearner->email,
        ));
    }

    /**
     * @param  list<array{label: string, is_correct: bool}>  $options
     */
    private function seedQuestion(Quiz $quiz, int $position, string $prompt, string $explanation, array $options): void
    {
        $question = QuizQuestion::factory()->create([
            'quiz_id' => $quiz->id,
            'position' => $position,
            'prompt' => $prompt,
            'explanation' => $explanation,
        ]);

        foreach ($options as $option) {
            QuizOption::factory()->create([
                'quiz_question_id' => $question->id,
                'label' => $option['label'],
                'is_correct' => $option['is_correct'],
            ]);
        }
    }
}
