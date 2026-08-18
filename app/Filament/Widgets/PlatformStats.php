<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        // Vue d'ensemble plateforme : reservee a l'administrateur.
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Apprenants', (string) User::query()->where('role', UserRole::Learner)->count())
                ->description('Comptes apprenants')
                ->color('primary'),

            Stat::make('Formations publiées', (string) Course::query()->where('status', CourseStatus::Published)->count())
                ->description('Visibles au catalogue')
                ->color('success'),

            Stat::make('Inscriptions actives', (string) Enrollment::query()->where('status', EnrollmentStatus::Active)->count())
                ->description('Accès en cours')
                ->color('warning'),
        ];
    }
}
