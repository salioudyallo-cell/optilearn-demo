<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Certificate;
use App\Models\LessonProgress;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPI d'engagement et de complétion — le cœur du pilotage produit (objectif « apprenants
 * actifs / complétion »). Calculés sur les données réelles de progression et de
 * certificats, donc fiables quelle que soit l'ancienneté du suivi d'événements.
 */
class EngagementStats extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $activeLearners = LessonProgress::query()
            ->where('updated_at', '>=', now()->subDays(7))
            ->distinct()
            ->count('user_id');

        $lessonsCompleted7d = LessonProgress::query()
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', now()->subDays(7))
            ->count();

        $certificates30d = Certificate::query()
            ->where('issued_at', '>=', now()->subDays(30))
            ->count();

        $certificatesTotal = Certificate::query()->count();

        return [
            Stat::make('Apprenants actifs (7 j)', (string) $activeLearners)
                ->description('Progression enregistrée sur 7 jours')
                ->color('primary'),

            Stat::make('Leçons terminées (7 j)', (string) $lessonsCompleted7d)
                ->description('Rythme d’apprentissage récent')
                ->color('info'),

            Stat::make('Formations terminées', (string) $certificatesTotal)
                ->description($certificates30d.' sur les 30 derniers jours')
                ->color('success'),
        ];
    }
}
