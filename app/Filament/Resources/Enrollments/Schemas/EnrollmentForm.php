<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enrollments\Schemas;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Apprenant')
                    ->relationship('user', 'name')
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required(),

                Select::make('course_id')
                    ->label('Formation')
                    ->relationship('course', 'title', fn (Builder $query) => self::scopeCoursesToOwner($query))
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('status')
                    ->label('Statut')
                    ->options(EnrollmentStatus::class)
                    ->default(EnrollmentStatus::Active)
                    ->required(),

                DateTimePicker::make('expires_at')
                    ->label('Expire le')
                    ->seconds(false)
                    ->helperText('Laisser vide pour un accès à vie.'),
            ]);
    }

    /**
     * @param  Builder<Course>  $query
     * @return Builder<Course>
     */
    private static function scopeCoursesToOwner(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user !== null && $user->isInstructor()) {
            $query->where('instructor_id', $user->getKey());
        }

        return $query;
    }
}
