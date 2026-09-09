<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\AccessCode;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Catalogue de demonstration : trois formations couvrant les trois niveaux, chacune
 * avec sa miniature, une lecon d'essai en acces libre, un quiz et un code d'acces.
 *
 * N'utilise AUCUNE factory : Faker est une dependance de developpement, absente en
 * production (composer install --no-dev). Ce seeder est donc executable sur le serveur.
 *
 * Idempotent : relancable sans doublon ni perte de progression.
 *
 *     php artisan db:seed --class=CatalogDemoSeeder --force
 *
 * Les miniatures sont generees ici (SVG aux couleurs de la charte) : aucune image
 * tierce, donc aucune question de licence, et rien a transferer par FTP.
 */
class CatalogDemoSeeder extends Seeder
{
    /**
     * Seule video disponible pour la demonstration : les trois formations la
     * reutilisent pour leur lecon d'essai.
     */
    private const VIDEO_PATH = 'demo-video.mp4';

    public function run(): void
    {
        if (! Storage::disk(config('lms.video.disk'))->exists(self::VIDEO_PATH)) {
            $this->command->warn(
                'Fichier '.self::VIDEO_PATH.' absent du disque « '.config('lms.video.disk')
                .' » : les leçons vidéo seront créées mais la lecture échouera '
                .'jusqu’au dépôt du fichier par FTP.'
            );
        }

        $instructor = $this->resolveInstructor();

        $this->seedDiscoveryCourse($instructor);
        $this->seedIntermediateCourse($instructor);
        $this->seedAdvancedCourse($instructor);

        $this->command->info('Catalogue de démonstration prêt : 3 formations, 3 couvertures photo.');
        $this->command->info('Codes d’accès : DEMOVIDEO1 · LEADSB2B24 · PILOTAGE01');
    }

    // =====================================================================
    //  Formation 1 — Debutant
    // =====================================================================

    private function seedDiscoveryCourse(User $instructor): void
    {
        $course = $this->upsertCourse([
            'slug' => 'demo-plateforme-video',
            'title' => 'Démonstration : prise en main de la plateforme',
            'subtitle' => 'Une formation courte pour découvrir le lecteur, le quiz et le certificat.',
            'description' => "## À qui s’adresse cette formation\n\n"
                .'À toute personne qui découvre la plateforme et souhaite vérifier le parcours '
                ."complet avant de s’engager sur un programme plus long.\n\n"
                ."## Ce que vous saurez faire\n\n"
                ."- Naviguer dans une formation et reprendre là où vous vous êtes arrêté\n"
                ."- Passer un quiz de validation et lire votre score\n"
                ."- Télécharger votre certificat et le faire vérifier\n\n"
                ."## Prérequis\n\n"
                .'Aucun. Un téléphone ou un ordinateur avec une connexion internet suffit.',
            'level' => CourseLevel::Debutant,
            'price_fcfa' => 0,
            'duration_minutes' => 15,
            'cover' => 'decouverte',
            'instructor' => $instructor,
        ]);

        $m1 = $this->upsertModule($course, 1, 'Découvrir le lecteur');
        $m2 = $this->upsertModule($course, 2, 'Valider ses acquis');

        $this->upsertLesson($m1, 1, 'Visite guidée de la plateforme', LessonType::Video, [
            'video_path' => self::VIDEO_PATH,
            'is_preview' => true,
        ]);

        $this->upsertLesson($m1, 2, 'Ce que la plateforme sait faire', LessonType::Text, [
            'content' => "## Suivi de progression\n\n"
                .'Votre position dans la vidéo est enregistrée automatiquement : vous reprenez '
                .'exactement là où vous vous étiez arrêté, même après avoir fermé le navigateur. '
                .'En cas de coupure réseau, la progression est conservée puis synchronisée au '
                ."retour de la connexion.\n\n"
                ."## Accès par code\n\n"
                .'Chaque formation s’ouvre avec un code d’accès nominatif, valable pour un nombre '
                ."d’utilisations défini. Aucun paiement en ligne n’est requis.\n\n"
                ."## Certificat\n\n"
                .'Le quiz réussi déclenche la génération d’un certificat PDF portant un numéro de '
                .'série vérifiable publiquement.',
        ]);

        $quiz = $this->upsertQuiz($this->upsertLesson($m2, 1, 'Quiz de validation', LessonType::Quiz), 70, 3);

        $this->upsertQuestion($quiz, 1,
            'Que se passe-t-il si vous quittez une leçon vidéo en cours de lecture ?',
            'La position est enregistrée périodiquement : la lecture reprend au même endroit.',
            [
                ['La progression est conservée et la lecture reprend au même endroit', true],
                ['La leçon repart de zéro', false],
                ['La leçon est marquée comme terminée', false],
                ['L’accès à la formation est perdu', false],
            ]);

        $this->upsertQuestion($quiz, 2,
            'Comment un apprenant obtient-il l’accès à une formation ?',
            'L’accès est accordé par un code nominatif, sans paiement en ligne.',
            [
                ['En saisissant un code d’accès fourni par OptiLeads', true],
                ['En payant par carte bancaire sur le site', false],
                ['En créant simplement un compte', false],
                ['En envoyant une demande par courrier', false],
            ]);

        $this->upsertQuestion($quiz, 3,
            'Que délivre la plateforme à l’issue d’un quiz réussi ?',
            'Un certificat PDF nominatif, dont le numéro de série est vérifiable publiquement.',
            [
                ['Un certificat PDF avec un numéro de série vérifiable', true],
                ['Un simple message de félicitations', false],
                ['Un remboursement partiel', false],
                ['Un accès à vie à toutes les formations', false],
            ]);

        $this->upsertAccessCode('DEMOVIDEO1', $course, $instructor, 20, 'Démonstration — parcours complet');
    }

    // =====================================================================
    //  Formation 2 — Intermediaire
    // =====================================================================

    private function seedIntermediateCourse(User $instructor): void
    {
        $course = $this->upsertCourse([
            'slug' => 'generer-des-leads-b2b-linkedin-emailing',
            'title' => 'Générer des leads B2B avec LinkedIn et l’e-mailing',
            'subtitle' => 'Construire un flux régulier de rendez-vous qualifiés, sans budget publicitaire.',
            'description' => "## À qui s’adresse cette formation\n\n"
                .'Aux commerciaux, consultants et dirigeants de PME en Afrique de l’Ouest qui '
                ."doivent générer leurs propres rendez-vous.\n\n"
                ."## Ce que vous saurez faire\n\n"
                ."- Définir un client cible précis et constituer une liste de prospects fiable\n"
                ."- Rédiger des messages qui obtiennent une réponse, pas seulement une lecture\n"
                ."- Enchaîner LinkedIn et e-mail dans une séquence cohérente\n"
                ."- Mesurer votre taux de réponse et corriger ce qui ne marche pas\n\n"
                ."## Prérequis\n\n"
                .'Un profil LinkedIn actif et une adresse e-mail professionnelle. Aucune '
                ."compétence technique n’est requise.\n\n"
                ."## Durée et format\n\n"
                .'3 h de contenu, réparties en 3 modules, à suivre à votre rythme.',
            'level' => CourseLevel::Intermediaire,
            'price_fcfa' => 120000,
            'duration_minutes' => 180,
            'cover' => 'prospection',
            'instructor' => $instructor,
        ]);

        $m1 = $this->upsertModule($course, 1, 'Cibler avant de prospecter');
        $m2 = $this->upsertModule($course, 2, 'Écrire des messages qui obtiennent une réponse');
        $m3 = $this->upsertModule($course, 3, 'Mesurer et valider');

        $this->upsertLesson($m1, 1, 'L’erreur qui ruine 80 % des prospections', LessonType::Video, [
            'video_path' => self::VIDEO_PATH,
            'is_preview' => true,
        ]);

        $this->upsertLesson($m1, 2, 'Définir votre client idéal en 4 critères', LessonType::Text, [
            'content' => "## Les 4 critères\n\n"
                ."1. **Le secteur** — là où votre offre a déjà fait ses preuves\n"
                ."2. **La taille** — celle où votre interlocuteur décide seul\n"
                ."3. **Le déclencheur** — l’événement qui rend votre offre urgente\n"
                ."4. **La zone** — un marché que vous pouvez servir sans surcoût\n\n"
                ."## Pourquoi c’est décisif\n\n"
                .'Un message moyen envoyé à la bonne personne obtient plus de réponses qu’un '
                ."message brillant envoyé à la mauvaise.\n\n"
                .'> Si votre liste contient plus de 200 noms au démarrage, elle est trop large.',
        ]);

        $this->upsertLesson($m2, 1, 'La structure d’un message en 5 lignes', LessonType::Text, [
            'content' => "## La structure\n\n"
                ."1. **L’accroche** — un fait vérifiable sur son entreprise\n"
                ."2. **Le constat** — le problème que ce fait implique\n"
                ."3. **La preuve** — un résultat obtenu chez un client comparable\n"
                ."4. **La demande** — une question fermée, facile à trancher\n"
                ."5. **La sortie** — une porte de sortie qui respecte son temps\n\n"
                ."## Ce qu’il faut supprimer\n\n"
                ."- Votre présentation en trois phrases : personne ne l’a demandée\n"
                ."- Les superlatifs (« leader », « innovant », « incontournable »)\n"
                .'- Toute pièce jointe dans un premier message',
        ]);

        $this->upsertLesson($m2, 2, 'Enchaîner LinkedIn et e-mail sur 12 jours', LessonType::Video, [
            'video_path' => self::VIDEO_PATH,
        ]);

        $quiz = $this->upsertQuiz($this->upsertLesson($m3, 1, 'Quiz : votre méthode est-elle solide ?', LessonType::Quiz), 70, 3);

        $this->upsertQuestion($quiz, 1,
            'Quel indicateur juge le mieux la qualité d’une séquence de prospection ?',
            'Le taux de réponse mesure l’intérêt réel ; l’ouverture ne prouve qu’un objet accrocheur.',
            [
                ['Le taux de réponse', true],
                ['Le taux d’ouverture', false],
                ['Le nombre de messages envoyés', false],
                ['La longueur du message', false],
            ]);

        $this->upsertQuestion($quiz, 2,
            'Que doit contenir la première ligne d’un message de prospection ?',
            'Un fait vérifiable sur le prospect prouve que le message lui est réellement destiné.',
            [
                ['Un fait précis et vérifiable sur son entreprise', true],
                ['Votre présentation et celle de votre société', false],
                ['La liste de vos références', false],
                ['Le tarif de votre prestation', false],
            ]);

        $this->upsertQuestion($quiz, 3,
            'Votre liste de prospects compte 900 contacts très différents. Quelle décision prendre ?',
            'Une liste trop large empêche de personnaliser : mieux vaut segmenter et réduire.',
            [
                ['La segmenter et n’en garder qu’un segment pour commencer', true],
                ['Envoyer le même message à tout le monde', false],
                ['Doubler le nombre d’envois quotidiens', false],
                ['Acheter une liste plus grande', false],
            ]);

        $this->upsertQuestion($quiz, 4,
            'Après 60 messages, vous obtenez 1 réponse. Quelle est la première chose à revoir ?',
            'Un taux de réponse très bas trahit d’abord un mauvais ciblage, avant la rédaction.',
            [
                ['Le ciblage de la liste', true],
                ['La couleur de votre signature', false],
                ['L’heure d’envoi', false],
                ['Le nombre de relances', false],
            ]);

        $this->upsertAccessCode('LEADSB2B24', $course, $instructor, 30, 'Cohorte prospection B2B');
    }

    // =====================================================================
    //  Formation 3 — Avance
    // =====================================================================

    private function seedAdvancedCourse(User $instructor): void
    {
        $course = $this->upsertCourse([
            'slug' => 'piloter-acquisition-par-la-donnee',
            'title' => 'Piloter l’acquisition par la donnée',
            'subtitle' => 'Attribution, coût par lead et tableaux de bord : décider avec des chiffres fiables.',
            'description' => "## À qui s’adresse cette formation\n\n"
                .'Aux responsables marketing et dirigeants qui investissent déjà sur plusieurs '
                ."canaux et veulent savoir lesquels rapportent réellement.\n\n"
                ."## Ce que vous saurez faire\n\n"
                ."- Calculer un coût par lead qualifié qui résiste à la contradiction\n"
                ."- Choisir un modèle d’attribution adapté à votre cycle de vente\n"
                ."- Construire un tableau de bord lisible en une minute\n"
                ."- Arbitrer un budget entre canaux sur la base des marges, pas des impressions\n\n"
                ."## Prérequis\n\n"
                .'Avoir déjà mené des campagnes sur au moins deux canaux et disposer d’un accès '
                ."à vos outils de mesure.\n\n"
                ."## Durée et format\n\n"
                .'4 h de contenu, 3 modules, avec un quiz exigeant à 80 %.',
            'level' => CourseLevel::Avance,
            'price_fcfa' => 250000,
            'duration_minutes' => 240,
            'cover' => 'pilotage',
            'instructor' => $instructor,
        ]);

        $m1 = $this->upsertModule($course, 1, 'Mesurer ce qui compte');
        $m2 = $this->upsertModule($course, 2, 'Attribution et arbitrage budgétaire');
        $m3 = $this->upsertModule($course, 3, 'Certification');

        $this->upsertLesson($m1, 1, 'Pourquoi vos indicateurs actuels vous trompent', LessonType::Video, [
            'video_path' => self::VIDEO_PATH,
            'is_preview' => true,
        ]);

        $this->upsertLesson($m1, 2, 'Du clic à la marge : la chaîne complète', LessonType::Text, [
            'content' => "## La chaîne de valeur\n\n"
                ."Clic → visite → lead → lead qualifié → opportunité → client → **marge**\n\n"
                .'Chaque étape a un taux de passage. Optimiser une étape sans regarder la '
                ."suivante déplace le problème sans le résoudre.\n\n"
                ."## Le calcul qui compte\n\n"
                ."**Coût par lead qualifié** = dépense du canal ÷ nombre de leads qualifiés\n\n"
                .'Un canal à 5 000 FCFA le lead dont 5 % se qualifient coûte plus cher qu’un '
                ."canal à 15 000 FCFA dont 40 % se qualifient.\n\n"
                .'> Tant que vous ne mesurez pas la qualification, vous optimisez à l’aveugle.',
        ]);

        $this->upsertLesson($m2, 1, 'Choisir son modèle d’attribution', LessonType::Text, [
            'content' => "## Trois modèles, trois usages\n\n"
                ."- **Dernier contact** : simple, mais surévalue les canaux de fin de parcours\n"
                ."- **Premier contact** : révèle ce qui crée la demande, ignore la conversion\n"
                ."- **Linéaire ou en U** : plus juste sur un cycle long, plus lourd à tenir\n\n"
                ."## La règle pratique\n\n"
                .'Si votre cycle de vente dépasse trois semaines et implique plusieurs points de '
                .'contact, le dernier contact vous fera couper les mauvais canaux.',
        ]);

        $this->upsertLesson($m2, 2, 'Construire le tableau de bord', LessonType::Video, [
            'video_path' => self::VIDEO_PATH,
        ]);

        $quiz = $this->upsertQuiz($this->upsertLesson($m3, 1, 'Certification : pilotage par la donnée', LessonType::Quiz), 80, 2);

        $this->upsertQuestion($quiz, 1,
            'Canal A : 5 000 FCFA le lead, 5 % qualifiés. Canal B : 15 000 FCFA le lead, 40 % qualifiés. Lequel est le moins cher ?',
            'A revient à 100 000 FCFA le lead qualifié, B à 37 500 FCFA : B est près de trois fois moins cher.',
            [
                ['Le canal B', true],
                ['Le canal A', false],
                ['Les deux sont équivalents', false],
                ['Impossible à déterminer', false],
            ]);

        $this->upsertQuestion($quiz, 2,
            'Votre cycle de vente dure deux mois avec cinq points de contact. Quel modèle d’attribution éviter ?',
            'Le dernier contact attribue tout au canal de clôture et fait couper les canaux qui créent la demande.',
            [
                ['Le dernier contact', true],
                ['Le modèle en U', false],
                ['Le modèle linéaire', false],
                ['Le premier contact', false],
            ]);

        $this->upsertQuestion($quiz, 3,
            'Quel indicateur doit figurer en premier sur un tableau de bord d’acquisition ?',
            'La marge générée est le seul chiffre qui relie l’acquisition au résultat de l’entreprise.',
            [
                ['La marge générée par canal', true],
                ['Le nombre d’impressions', false],
                ['Le nombre d’abonnés', false],
                ['Le taux de rebond', false],
            ]);

        $this->upsertQuestion($quiz, 4,
            'Un canal affiche un excellent coût par lead mais aucune vente en trois mois. Que faire ?',
            'Un lead qui ne se transforme jamais n’est pas un lead : il faut vérifier la qualification avant de conclure.',
            [
                ['Vérifier la qualification des leads avant d’augmenter le budget', true],
                ['Augmenter immédiatement le budget de ce canal', false],
                ['Conserver le canal tel quel puisque le coût est bas', false],
                ['Supprimer tous les autres canaux', false],
            ]);

        $this->upsertQuestion($quiz, 5,
            'Sur quelle base arbitrer un budget entre deux canaux ?',
            'On compare des marges par franc investi, pas des volumes de trafic.',
            [
                ['La marge générée par franc investi', true],
                ['Le volume de trafic apporté', false],
                ['La notoriété perçue du canal', false],
                ['Le coût par clic le plus bas', false],
            ]);

        $this->upsertAccessCode('PILOTAGE01', $course, $instructor, 15, 'Cohorte pilotage — direction marketing');
    }

    // =====================================================================
    //  Fabriques idempotentes
    // =====================================================================

    /**
     * @param  array<string, mixed>  $data
     */
    private function upsertCourse(array $data): Course
    {
        /** @var User $instructor */
        $instructor = $data['instructor'];

        return Course::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'],
                'description' => $data['description'],
                'level' => $data['level'],
                'status' => CourseStatus::Published,
                'price_fcfa' => $data['price_fcfa'],
                'duration_minutes' => $data['duration_minutes'],
                'instructor_id' => $instructor->getKey(),
                'cover_path' => $this->writeCover($data['cover']),
                'published_at' => now(),
            ]
        );
    }

    private function upsertModule(Course $course, int $position, string $title): Module
    {
        return Module::updateOrCreate(
            ['course_id' => $course->getKey(), 'position' => $position],
            ['title' => $title]
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertLesson(Module $module, int $position, string $title, LessonType $type, array $attributes = []): Lesson
    {
        return Lesson::updateOrCreate(
            ['module_id' => $module->getKey(), 'position' => $position],
            array_merge([
                'title' => $title,
                'type' => $type,
                'is_preview' => false,
                'duration_seconds' => 0,
            ], $attributes)
        );
    }

    private function upsertQuiz(Lesson $lesson, int $passScore, int $maxAttempts): Quiz
    {
        return Quiz::updateOrCreate(
            ['lesson_id' => $lesson->getKey()],
            ['pass_score_pct' => $passScore, 'max_attempts' => $maxAttempts]
        );
    }

    /**
     * @param  list<array{0: string, 1: bool}>  $options
     */
    private function upsertQuestion(Quiz $quiz, int $position, string $prompt, string $explanation, array $options): void
    {
        $question = QuizQuestion::updateOrCreate(
            ['quiz_id' => $quiz->getKey(), 'position' => $position],
            ['prompt' => $prompt, 'explanation' => $explanation]
        );

        // Remplacement en bloc : evite les doublons a la relance.
        $question->options()->delete();

        foreach ($options as [$label, $isCorrect]) {
            QuizOption::create([
                'quiz_question_id' => $question->getKey(),
                'label' => $label,
                'is_correct' => $isCorrect,
            ]);
        }
    }

    private function upsertAccessCode(string $code, Course $course, User $creator, int $maxUses, string $label): void
    {
        AccessCode::updateOrCreate(
            ['code' => $code],
            [
                'course_id' => $course->getKey(),
                'max_uses' => $maxUses,
                'is_active' => true,
                'label' => $label,
                'created_by' => $creator->getKey(),
            ]
        );
    }

    // =====================================================================
    //  Miniatures
    // =====================================================================

    /**
     * Ecrit la couverture sur le disque des medias et renvoie son chemin relatif.
     *
     * Priorite a une vraie photo versionnee dans public/images/demo-covers/<name>.jpg
     * (banque libre de droits, deposee au depot). En son absence, repli sur une
     * miniature SVG generee (aucune image tierce, remplacable au back-office).
     */
    private function writeCover(string $name): string
    {
        $disk = Storage::disk(config('lms.courses.media_disk'));

        // Photo fournie (libre de droits) : copiee telle quelle sur le disque des medias.
        $photo = public_path('images/demo-covers/'.$name.'.jpg');
        if (File::isFile($photo)) {
            $path = 'courses/covers/'.$name.'.jpg';
            $disk->put($path, File::get($photo));

            return $path;
        }

        /** @var array<string, array{0: string, 1: string, 2: string}> $palettes */
        $palettes = [
            // [debut du degrade, fin du degrade, couleur d'accent]
            'decouverte' => ['#1a56d6', '#0b2a6b', '#f97600'],
            'prospection' => ['#f97600', '#b34e00', '#ffc101'],
            'pilotage' => ['#0f1420', '#1a56d6', '#16a34a'],
        ];

        $palette = $palettes[$name] ?? $palettes['decouverte'];
        $path = 'courses/covers/'.$name.'.svg';

        $disk->put($path, $this->coverSvg(...$palette));

        return $path;
    }

    /**
     * Visuel abstrait : dégradé de marque, maillage géométrique et halo d'accent.
     * Aucun texte, pour ne pas doublonner avec le titre affiché sur la carte.
     */
    private function coverSvg(string $from, string $to, string $accent): string
    {
        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 675" width="1200" height="675" role="img">
              <defs>
                <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                  <stop offset="0%" stop-color="{$from}"/>
                  <stop offset="100%" stop-color="{$to}"/>
                </linearGradient>
                <radialGradient id="halo" cx="0.78" cy="0.22" r="0.55">
                  <stop offset="0%" stop-color="{$accent}" stop-opacity="0.55"/>
                  <stop offset="100%" stop-color="{$accent}" stop-opacity="0"/>
                </radialGradient>
                <pattern id="grid" width="60" height="60" patternUnits="userSpaceOnUse">
                  <path d="M60 0H0V60" fill="none" stroke="#ffffff" stroke-opacity="0.07" stroke-width="1"/>
                </pattern>
              </defs>
              <rect width="1200" height="675" fill="url(#bg)"/>
              <rect width="1200" height="675" fill="url(#grid)"/>
              <rect width="1200" height="675" fill="url(#halo)"/>
              <g fill="none" stroke="#ffffff" stroke-opacity="0.16" stroke-width="2">
                <circle cx="935" cy="150" r="90"/>
                <circle cx="935" cy="150" r="150"/>
                <circle cx="935" cy="150" r="215"/>
              </g>
              <path d="M0 560 C 220 470 380 600 620 500 C 820 415 1000 470 1200 400 L1200 675 L0 675 Z"
                    fill="#ffffff" fill-opacity="0.06"/>
              <path d="M0 620 C 260 545 420 655 660 570 C 880 495 1030 545 1200 490"
                    fill="none" stroke="{$accent}" stroke-opacity="0.75" stroke-width="4"/>
              <g fill="{$accent}">
                <circle cx="660" cy="570" r="9"/>
                <circle cx="1200" cy="490" r="9"/>
              </g>
            </svg>
            SVG;
    }

    /**
     * Rattache les formations a un formateur existant, sinon a un administrateur.
     * Ce seeder ne cree aucun compte : en production, les comptes sont crees
     * explicitement avec « php artisan app:create-admin ».
     */
    private function resolveInstructor(): User
    {
        $user = User::query()
            ->whereIn('role', [UserRole::Instructor->value, UserRole::Admin->value])
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [UserRole::Instructor->value])
            ->first();

        if ($user === null) {
            throw new RuntimeException(
                'Aucun compte formateur ou administrateur trouvé : créez-en un avec '
                .'« php artisan app:create-admin » avant de lancer ce seeder.'
            );
        }

        return $user;
    }
}
