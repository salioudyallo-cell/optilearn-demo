<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property UserRole $role
 * @property string|null $avatar_path
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $suspended_at
 * @property Carbon|null $created_at
 */
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Effacement RGPD : à la suppression du compte, on retire aussi les fichiers
     * personnels (PDF de certificats, avatar). Les lignes liées sont supprimées par
     * les contraintes de cascade de la base.
     */
    protected static function booted(): void
    {
        static::deleting(function (User $user): void {
            $user->certificates()->get()->each(function (Certificate $certificate): void {
                if (filled($certificate->pdf_path)) {
                    Storage::disk(config('lms.certificates.disk'))->delete($certificate->pdf_path);
                }
            });

            if (filled($user->avatar_path)) {
                Storage::disk(config('lms.courses.media_disk'))->delete($user->avatar_path);
            }
        });
    }

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'company',
        'country',
        'role',
        'avatar_path',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->isSuspended() && $this->role->canAccessAdminPanel();
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isInstructor(): bool
    {
        return $this->role === UserRole::Instructor;
    }

    public function isLearner(): bool
    {
        return $this->role === UserRole::Learner;
    }

    /**
     * Une inscription active et non echue sur ce cours. Utilise par les Policies :
     * c'est le seul point de verite de l'acces au contenu payant.
     */
    public function hasAccessToCourse(Course $course): bool
    {
        return $this->enrollments()
            ->where('course_id', $course->getKey())
            ->grantingAccess()
            ->exists();
    }

    /** @return HasMany<Course, $this> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsToMany<Group, $this> */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_user');
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /** @return HasMany<LessonProgress, $this> */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /** @return HasMany<Certificate, $this> */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /** @return HasMany<AccessCodeRedemption, $this> */
    public function accessCodeRedemptions(): HasMany
    {
        return $this->hasMany(AccessCodeRedemption::class);
    }
}
