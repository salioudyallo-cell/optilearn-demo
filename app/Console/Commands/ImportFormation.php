<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Pont Formation Studio -> LMS.
 *
 * Importe une formation produite dans Formation Studio (arborescence markdown) sous forme
 * de Cours / Modules / Lecons / Quiz. Un niveau (debutant/intermediaire/avance) = un cours.
 *
 * Trois modes :
 *   --studio=<chemin>            analyse le markdown Studio (source de verite)
 *   --studio=<chemin> --export   ecrit le JSON compile dans database/content/<slug>.json
 *                                (le markdown n'est PAS transfere par FTP : le JSON, si)
 *   (sans option)                lit database/content/<slug>.json puis importe en base
 *
 * Idempotent : relancable sans doublon (updateOrCreate sur slug/position).
 *
 *   # En local, valider l'analyse sans base :
 *   php artisan app:import-formation ia-outils-digitaux --studio="C:\\...\\ia-outils-digitaux-oudalaye" --export
 *   # Importer en base :
 *   php artisan app:import-formation ia-outils-digitaux --studio="C:\\..."
 *   # Sur le serveur (JSON deploye) :
 *   php artisan app:import-formation ia-outils-digitaux
 */
class ImportFormation extends Command
{
    protected $signature = 'app:import-formation
        {slug : identifiant de base (ex. ia-outils-digitaux)}
        {--studio= : chemin du dossier Formation Studio a analyser}
        {--export : ecrire le JSON compile sans importer}
        {--level=* : limiter aux niveaux (debutant|intermediaire|avance)}
        {--title= : titre de base (defaut : lu dans programme.md)}
        {--subtitle= : sous-titre (defaut : objectif final du programme)}
        {--course-level=intermediaire : niveau si formation a un seul niveau}';

    protected $description = 'Importe une formation Formation Studio (markdown ou JSON compile) dans le catalogue LMS.';

    /**
     * @var array<string, array{label: string, enum: CourseLevel, subdir: string, palette: array{0: string, 1: string, 2: string}}>
     */
    private const LEVELS = [
        'debutant' => ['label' => 'Débutant', 'enum' => CourseLevel::Debutant, 'subdir' => '', 'palette' => ['#1a56d6', '#0b2a6b', '#f97600']],
        'intermediaire' => ['label' => 'Intermédiaire', 'enum' => CourseLevel::Intermediaire, 'subdir' => 'intermediaire', 'palette' => ['#f97600', '#b34e00', '#ffc101']],
        'avance' => ['label' => 'Avancé', 'enum' => CourseLevel::Avance, 'subdir' => 'avance', 'palette' => ['#0f1420', '#1a56d6', '#16a34a']],
    ];

    /**
     * Tarifs de la plateforme de démonstration (FCFA), par slug de cours.
     * Ils illustrent la vente en ligne face au prospect. Les cours non listés
     * restent à 0 (accès par code).
     */
    private const DEMO_PRICES = [
        'ia-outils-digitaux-debutant' => 25000,
        'ia-outils-digitaux-intermediaire' => 45000,
        'ia-outils-digitaux-avance' => 65000,
        'acquisition-b2b' => 75000,
    ];

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        /** @var list<string> $onlyLevels */
        $onlyLevels = (array) $this->option('level');
        $studio = $this->option('studio');
        $title = $this->option('title');
        $subtitle = $this->option('subtitle');
        $courseLevel = (string) ($this->option('course-level') ?: 'intermediaire');

        if (is_string($studio) && $studio !== '') {
            $data = $this->parseStudio(
                $studio,
                $slug,
                is_string($title) ? $title : null,
                is_string($subtitle) ? $subtitle : null,
                $courseLevel,
                $onlyLevels
            );
        } else {
            $file = database_path("content/{$slug}.json");
            if (! File::isFile($file)) {
                $this->error("JSON introuvable : {$file}. Lancez d'abord avec --studio=<chemin> --export.");

                return self::FAILURE;
            }
            /** @var array{base_slug: string, levels: array<string, mixed>} $data */
            $data = json_decode(File::get($file), true, 512, JSON_THROW_ON_ERROR);
        }

        if ($this->option('export')) {
            $dir = database_path('content');
            File::ensureDirectoryExists($dir);
            $path = "{$dir}/{$slug}.json";
            File::put($path, (string) json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->info("JSON compilé : {$path}");
            $this->summarise($data);

            return self::SUCCESS;
        }

        $this->importData($data);

        return self::SUCCESS;
    }

    // =====================================================================
    //  Analyse de l'arborescence Formation Studio
    // =====================================================================

    /**
     * @param  list<string>  $onlyLevels
     * @return array{base_slug: string, levels: array<string, mixed>}
     */
    private function parseStudio(string $root, string $slug, ?string $title, ?string $subtitle, string $courseLevel, array $onlyLevels): array
    {
        if (! File::isDirectory($root)) {
            throw new RuntimeException("Dossier Studio introuvable : {$root}");
        }

        [$progTitle, $progSubtitle] = $this->programmeMeta($root);
        $baseTitle = $title ?? $progTitle ?? $slug;
        $baseSubtitle = $subtitle ?? $progSubtitle ?? '';

        // Plusieurs niveaux si des sous-dossiers intermediaire/avance existent.
        $multiLevel = File::isDirectory($root.'/01-modules/intermediaire')
            || File::isDirectory($root.'/01-modules/avance');

        $levels = [];

        if ($multiLevel) {
            foreach (self::LEVELS as $key => $meta) {
                if ($onlyLevels !== [] && ! in_array($key, $onlyLevels, true)) {
                    continue;
                }
                $modulesDir = $root.'/01-modules'.($meta['subdir'] !== '' ? '/'.$meta['subdir'] : '');
                $modules = $this->parseModules($modulesDir);
                if ($modules === []) {
                    continue;
                }
                $levels[$key] = [
                    'title' => $baseTitle.' — Niveau '.$meta['label'],
                    'subtitle' => $baseTitle.', niveau '.mb_strtolower($meta['label']).'.',
                    'slug' => $slug.'-'.$key,
                    'level' => $key,
                    'description' => $this->levelDescription($meta['label'], $modules),
                    'modules' => $modules,
                ];
            }

            return ['base_slug' => $slug, 'levels' => $levels];
        }

        // Un seul niveau : un cours unique.
        $modules = $this->parseModules($root.'/01-modules');
        if ($modules !== []) {
            $key = array_key_exists($courseLevel, self::LEVELS) ? $courseLevel : 'intermediaire';
            $levels[$key] = [
                'title' => $baseTitle,
                'subtitle' => $baseSubtitle !== '' ? $baseSubtitle : $baseTitle,
                'slug' => $slug,
                'level' => $key,
                'description' => $this->levelDescription(null, $modules),
                'modules' => $modules,
            ];
        }

        return ['base_slug' => $slug, 'levels' => $levels];
    }

    /**
     * @return list<array{title: string, position: int, lessons: list<array<string, mixed>>}>
     */
    private function parseModules(string $modulesDir): array
    {
        $modules = [];
        $position = 0;
        foreach ($this->moduleDirs($modulesDir) as $dir) {
            $position++;
            $modules[] = $this->parseModule($dir, $position, $position === 1);
        }

        return $modules;
    }

    /**
     * Titre et sous-titre lus dans le programme (fallback si non fournis en option).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function programmeMeta(string $root): array
    {
        foreach (['00-conception/programme.md', '00-conception/programme-3-niveaux.md'] as $rel) {
            $file = $root.'/'.$rel;
            if (! File::isFile($file)) {
                continue;
            }
            [, $body] = $this->readParts($file);

            $title = $this->h1($body);
            if ($title !== null) {
                $title = trim((string) preg_replace('/^Programme\s*(des\s+\d+\s+niveaux)?\s*[—:\-]\s*/ui', '', $title));
                if ($title === '' || Str::lower($title) === 'de formation') {
                    $title = null;
                }
            }

            $subtitle = null;
            if (preg_match('/\*\*Objectif final\*\*\s*:\s*(.+)/u', $body, $m) === 1) {
                $subtitle = trim($m[1]);
                if (mb_strlen($subtitle) > 180) {
                    $subtitle = mb_substr($subtitle, 0, 177).'...';
                }
            }

            return [$title, $subtitle];
        }

        return [null, null];
    }

    /**
     * @return list<string>
     */
    private function moduleDirs(string $dir): array
    {
        if (! File::isDirectory($dir)) {
            return [];
        }

        $dirs = [];
        foreach (File::directories($dir) as $d) {
            if (Str::startsWith(basename($d), 'module-')) {
                $dirs[] = $d;
            }
        }
        natsort($dirs);

        return array_values($dirs);
    }

    /**
     * @return array{title: string, position: int, lessons: list<array<string, mixed>>}
     */
    private function parseModule(string $dir, int $position, bool $isFirstModule): array
    {
        $title = $this->cleanTitle(
            $this->fileTitle($dir.'/00-module.md', basename($dir)),
            '/^Module\s+\S+\s*:\s*/iu'
        );

        $lessons = [];
        $lessonPos = 0;

        // Lecons de cours (hors scripts video).
        foreach ($this->sortedFiles($dir.'/cours', 'lecon-') as $file) {
            $lessonPos++;
            [$fm, $body] = $this->readParts($file);
            $lessons[] = [
                'title' => $this->cleanTitle($fm['titre'] ?? $this->h1($body) ?? basename($file), '/^Leçon\s+\S+\s*:\s*/iu'),
                'type' => 'text',
                'content' => trim($this->stripLeadingH1($body)),
                'is_preview' => $isFirstModule && $lessonPos === 1,
            ];
        }

        // Exercices pratiques regroupes en une lecon texte (enonces seuls).
        $exercises = $this->sortedFiles($dir.'/exercices', 'exercice-');
        if ($exercises !== []) {
            $lessonPos++;
            $blocks = [];
            foreach ($exercises as $file) {
                [, $body] = $this->readParts($file);
                $blocks[] = trim($this->demote($body));
            }
            $lessons[] = [
                'title' => 'Exercices pratiques',
                'type' => 'text',
                'content' => "## Exercices pratiques\n\n".implode("\n\n---\n\n", $blocks),
                'is_preview' => false,
            ];
        }

        // Quiz de fin de module.
        $quiz = $this->parseQuizDir($dir.'/evaluation');
        if ($quiz !== null) {
            $lessonPos++;
            $lessons[] = [
                'title' => 'Quiz de validation',
                'type' => 'quiz',
                'quiz' => $quiz,
                'is_preview' => false,
            ];
        }

        return ['title' => $title, 'position' => $position, 'lessons' => $lessons];
    }

    // =====================================================================
    //  Analyse d'un quiz (QCM + Vrai/Faux ; questions ouvertes ignorees)
    // =====================================================================

    /**
     * @return array{pass_score_pct: int, max_attempts: int, questions: list<array<string, mixed>>}|null
     */
    private function parseQuizDir(string $dir): ?array
    {
        if (! File::isDirectory($dir)) {
            return null;
        }

        $quizFile = $this->firstFile($dir, 'quiz-');
        $corrigeFile = $this->firstFile($dir, 'corrige-');
        if ($quizFile === null) {
            return null;
        }

        [, $quizBody] = $this->readParts($quizFile);
        $corriges = $corrigeFile !== null ? $this->parseCorrige($this->readParts($corrigeFile)[1]) : [];

        // On restreint a la section "## Questions" (jusqu'au bareme).
        $section = $this->between($quizBody, '## Questions', '## Barème');

        $questions = [];
        $position = 0;
        foreach ($this->splitQuestions($section) as [$num, $header, $block]) {
            $type = $this->questionType($header);
            if ($type === 'open') {
                continue; // mise en situation : non auto-corrigeable
            }

            $correct = $corriges[$num] ?? null;
            if ($correct === null) {
                continue;
            }

            if ($type === 'vf') {
                $prompt = trim(preg_replace('/^Affirmation\s*:\s*/u', '', trim($block)) ?? trim($block));
                $isTrue = strtolower($correct['value']) === 'vrai';
                $options = [
                    ['label' => 'Vrai', 'is_correct' => $isTrue],
                    ['label' => 'Faux', 'is_correct' => ! $isTrue],
                ];
            } else { // qcm
                [$prompt, $options] = $this->parseChoices($block, $correct['value']);
                if ($options === []) {
                    continue;
                }
            }

            $position++;
            $questions[] = [
                'position' => $position,
                'prompt' => $prompt,
                'explanation' => $correct['explanation'],
                'options' => $options,
            ];
        }

        if ($questions === []) {
            return null;
        }

        return ['pass_score_pct' => 60, 'max_attempts' => 3, 'questions' => $questions];
    }

    /**
     * Decoupe la section en blocs de question : [numero, entete, corps].
     *
     * @return list<array{0: int, 1: string, 2: string}>
     */
    private function splitQuestions(string $section): array
    {
        $parts = preg_split('/^#{2,3}\s+Q(\d+)\b(.*)$/mu', $section, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return [];
        }

        $out = [];
        // $parts = [avant, num1, header1, body1, num2, header2, body2, ...]
        $total = count($parts);
        for ($i = 1; $i + 2 < $total; $i += 3) {
            $out[] = [(int) $parts[$i], (string) $parts[$i + 1], (string) $parts[$i + 2]];
        }

        return $out;
    }

    private function questionType(string $header): string
    {
        $h = Str::lower($header);
        if (Str::contains($h, 'vrai')) {
            return 'vf';
        }
        if (Str::contains($h, 'qcm')) {
            return 'qcm';
        }

        return 'open';
    }

    /**
     * @return array{0: string, 1: list<array{label: string, is_correct: bool}>}
     */
    private function parseChoices(string $block, string $correctLetter): array
    {
        $lines = preg_split('/\r?\n/', trim($block)) ?: [];
        $promptLines = [];
        $options = [];
        $letters = [];
        foreach ($lines as $line) {
            if (preg_match('/^-\s*([a-dA-D])\)\s*(.+)$/u', trim($line), $m) === 1) {
                $letters[] = strtolower($m[1]);
                $options[] = ['label' => trim($m[2]), 'is_correct' => strtolower($m[1]) === strtolower($correctLetter)];
            } elseif ($options === []) {
                $promptLines[] = trim($line);
            }
        }

        return [trim(implode(' ', array_filter($promptLines))), $options];
    }

    /**
     * Corrige -> map[numero] = [value, explanation].
     *
     * @return array<int, array{value: string, explanation: string}>
     */
    private function parseCorrige(string $body): array
    {
        $parts = preg_split('/^#{2,3}\s+Q(\d+)\b.*$/mu', $body, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return [];
        }

        $map = [];
        $total = count($parts);
        for ($i = 1; $i + 1 < $total; $i += 2) {
            $num = (int) $parts[$i];
            $seg = (string) $parts[$i + 1];

            $value = '';
            if (preg_match('/Bonne réponse\s*:\s*\**\s*([a-dA-D])\)/u', $seg, $m) === 1) {
                $value = strtolower($m[1]);
            } elseif (preg_match('/Réponse\s*:\s*\**\s*(Vrai|Faux)/ui', $seg, $m) === 1) {
                $value = $m[1];
            }

            $explanation = '';
            if (preg_match('/\*Pourquoi\*\s*:\s*(.+)/u', $seg, $m) === 1) {
                $explanation = trim($m[1]);
            }

            if ($value !== '') {
                $map[$num] = ['value' => $value, 'explanation' => $explanation];
            }
        }

        return $map;
    }

    // =====================================================================
    //  Import en base
    // =====================================================================

    /**
     * @param  array{base_slug: string, levels: array<string, mixed>}  $data
     */
    private function importData(array $data): void
    {
        $instructor = $this->resolveInstructor();
        $courses = 0;
        $modules = 0;
        $lessons = 0;
        $questions = 0;

        /** @var array<string, mixed> $level */
        foreach ($data['levels'] as $level) {
            $meta = self::LEVELS[$level['level']] ?? self::LEVELS['intermediaire'];

            /** @var Course $course */
            $course = Course::updateOrCreate(
                ['slug' => $level['slug']],
                [
                    'title' => $level['title'],
                    'subtitle' => $level['subtitle'] ?? $level['title'],
                    'description' => $level['description'],
                    'level' => $meta['enum'],
                    'status' => CourseStatus::Published,
                    'price_fcfa' => self::DEMO_PRICES[$level['slug']] ?? 0,
                    'duration_minutes' => 0,
                    'instructor_id' => $instructor->getKey(),
                    'cover_path' => $this->writeCover($level['slug'], $meta['palette']),
                    'published_at' => now(),
                ]
            );
            $courses++;

            /** @var array<string, mixed> $mod */
            foreach ($level['modules'] as $mod) {
                /** @var Module $module */
                $module = Module::updateOrCreate(
                    ['course_id' => $course->getKey(), 'position' => $mod['position']],
                    ['title' => $mod['title']]
                );
                $modules++;

                $pos = 0;
                /** @var array<string, mixed> $les */
                foreach ($mod['lessons'] as $les) {
                    $pos++;
                    if (($les['type'] ?? 'text') === 'quiz') {
                        $lesson = $this->upsertLesson($module, $pos, $les['title'], LessonType::Quiz, ['is_preview' => false]);
                        $questions += $this->upsertQuiz($lesson, $les['quiz']);
                    } else {
                        $this->upsertLesson($module, $pos, $les['title'], LessonType::Text, [
                            'content' => $les['content'] ?? '',
                            'is_preview' => (bool) ($les['is_preview'] ?? false),
                        ]);
                    }
                    $lessons++;
                }
            }

            $this->line("  Cours « {$level['title']} » : ".count($level['modules']).' modules.');
        }

        $this->newLine();
        $this->info("Import terminé : {$courses} cours, {$modules} modules, {$lessons} leçons, {$questions} questions de quiz.");
        $this->warn('Pensez à créer un code d’accès par cours dans le back-office (Codes d’accès).');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertLesson(Module $module, int $position, string $title, LessonType $type, array $attributes): Lesson
    {
        return Lesson::updateOrCreate(
            ['module_id' => $module->getKey(), 'position' => $position],
            array_merge(['title' => $title, 'type' => $type, 'duration_seconds' => 0, 'is_preview' => false], $attributes)
        );
    }

    /**
     * @param  array{pass_score_pct: int, max_attempts: int, questions: list<array<string, mixed>>}  $data
     */
    private function upsertQuiz(Lesson $lesson, array $data): int
    {
        /** @var Quiz $quiz */
        $quiz = Quiz::updateOrCreate(
            ['lesson_id' => $lesson->getKey()],
            ['pass_score_pct' => $data['pass_score_pct'], 'max_attempts' => $data['max_attempts']]
        );

        $count = 0;
        /** @var array<string, mixed> $q */
        foreach ($data['questions'] as $q) {
            /** @var QuizQuestion $question */
            $question = QuizQuestion::updateOrCreate(
                ['quiz_id' => $quiz->getKey(), 'position' => $q['position']],
                ['prompt' => $q['prompt'], 'explanation' => $q['explanation'] ?? '']
            );
            $question->options()->delete();
            /** @var array{label: string, is_correct: bool} $opt */
            foreach ($q['options'] as $opt) {
                QuizOption::create([
                    'quiz_question_id' => $question->getKey(),
                    'label' => $opt['label'],
                    'is_correct' => $opt['is_correct'],
                ]);
            }
            $count++;
        }

        return $count;
    }

    // =====================================================================
    //  Utilitaires markdown & fichiers
    // =====================================================================

    /**
     * @return array{0: array<string, string>, 1: string}
     */
    private function readParts(string $file): array
    {
        $raw = File::get($file);
        if (preg_match('/^---\r?\n(.*?)\r?\n---\r?\n(.*)$/su', $raw, $m) === 1) {
            return [$this->frontmatter($m[1]), $m[2]];
        }

        return [[], $raw];
    }

    /**
     * @return array<string, string>
     */
    private function frontmatter(string $block): array
    {
        $fm = [];
        foreach (preg_split('/\r?\n/', $block) ?: [] as $line) {
            if (preg_match('/^([a-zA-Z0-9_]+)\s*:\s*(.*)$/', $line, $m) === 1) {
                // Retire les guillemets encadrants d'une valeur YAML (ex. titre: "Module IM02 : ...").
                $fm[$m[1]] = trim(trim($m[2]), "\"'");
            }
        }

        return $fm;
    }

    private function fileTitle(string $file, string $fallback): string
    {
        if (! File::isFile($file)) {
            return $fallback;
        }
        [$fm, $body] = $this->readParts($file);

        return $fm['titre'] ?? $this->h1($body) ?? $fallback;
    }

    private function h1(string $body): ?string
    {
        if (preg_match('/^#\s+(.+)$/mu', $body, $m) === 1) {
            return trim($m[1]);
        }

        return null;
    }

    private function stripLeadingH1(string $body): string
    {
        return (string) preg_replace('/^\s*#\s+.+\r?\n/u', '', ltrim($body), 1);
    }

    /**
     * Retrograde les titres (H1 -> H2) pour l'insertion dans une lecon composite.
     */
    private function demote(string $body): string
    {
        return (string) preg_replace('/^#\s+/mu', '## ', trim($body));
    }

    private function cleanTitle(string $title, string $pattern): string
    {
        return trim((string) preg_replace($pattern, '', trim($title)));
    }

    private function between(string $text, string $start, string $end): string
    {
        $s = mb_strpos($text, $start);
        if ($s === false) {
            return $text;
        }
        $s += mb_strlen($start);
        $e = mb_strpos($text, $end, $s);

        return $e === false ? mb_substr($text, $s) : mb_substr($text, $s, $e - $s);
    }

    /**
     * @return list<string>
     */
    private function sortedFiles(string $dir, string $prefix): array
    {
        if (! File::isDirectory($dir)) {
            return [];
        }
        $files = [];
        foreach (File::files($dir) as $f) {
            $name = $f->getFilename();
            if (Str::startsWith($name, $prefix) && Str::endsWith($name, '.md')) {
                $files[] = $f->getPathname();
            }
        }
        natsort($files);

        return array_values($files);
    }

    private function firstFile(string $dir, string $prefix): ?string
    {
        $files = $this->sortedFiles($dir, $prefix);

        return $files[0] ?? null;
    }

    /**
     * @param  list<array{title: string, position: int, lessons: list<array<string, mixed>>}>  $modules
     */
    private function levelDescription(?string $label, array $modules): string
    {
        $lines = [$label !== null ? "## Programme (niveau {$label})" : '## Programme', ''];
        foreach ($modules as $m) {
            $lines[] = '- '.$m['title'];
        }
        $lines[] = '';
        $lines[] = 'Chaque module comprend des leçons, des exercices pratiques et un quiz de validation.';

        return implode("\n", $lines);
    }

    /**
     * @param  array{base_slug: string, levels: array<string, mixed>}  $data
     */
    private function summarise(array $data): void
    {
        /** @var array<string, mixed> $level */
        foreach ($data['levels'] as $key => $level) {
            $modules = count($level['modules']);
            $lessons = 0;
            $questions = 0;
            /** @var array<string, mixed> $m */
            foreach ($level['modules'] as $m) {
                $lessons += count($m['lessons']);
                /** @var array<string, mixed> $l */
                foreach ($m['lessons'] as $l) {
                    if (($l['type'] ?? '') === 'quiz') {
                        $questions += count($l['quiz']['questions']);
                    }
                }
            }
            $this->line("  {$key} : {$modules} modules, {$lessons} leçons, {$questions} questions.");
        }
    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $palette
     */
    private function writeCover(string $name, array $palette): string
    {
        $disk = Storage::disk(config('lms.courses.media_disk'));

        // Photo fournie (libre de droits) : public/images/course-covers/<slug>.jpg.
        $photo = public_path('images/course-covers/'.$name.'.jpg');
        if (File::isFile($photo)) {
            $path = 'courses/covers/'.$name.'.jpg';
            $disk->put($path, File::get($photo));

            return $path;
        }

        $path = 'courses/covers/'.$name.'.svg';
        [$from, $to, $accent] = $palette;

        $disk->put($path, <<<SVG
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
              </defs>
              <rect width="1200" height="675" fill="url(#bg)"/>
              <rect width="1200" height="675" fill="url(#halo)"/>
              <g fill="none" stroke="#ffffff" stroke-opacity="0.16" stroke-width="2">
                <circle cx="935" cy="150" r="90"/><circle cx="935" cy="150" r="150"/><circle cx="935" cy="150" r="215"/>
              </g>
              <path d="M0 620 C 260 545 420 655 660 570 C 880 495 1030 545 1200 490"
                    fill="none" stroke="{$accent}" stroke-opacity="0.8" stroke-width="4"/>
            </svg>
            SVG);

        return $path;
    }

    private function resolveInstructor(): User
    {
        $user = User::query()
            ->whereIn('role', [UserRole::Instructor->value, UserRole::Admin->value])
            ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [UserRole::Instructor->value])
            ->first();

        if ($user === null) {
            throw new RuntimeException('Aucun formateur ou administrateur trouvé : créez-en un avant l’import.');
        }

        return $user;
    }
}
