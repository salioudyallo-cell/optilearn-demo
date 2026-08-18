<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use Database\Factories\EnrollmentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property EnrollmentStatus $status
 * @property EnrollmentSource $source
 * @property Carbon|null $expires_at
 * @property Carbon $enrolled_at
 */
class Enrollment extends Model
{
    /** @use HasFactory<EnrollmentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'course_id',
        'status',
        'source',
        'access_code_id',
        'order_id',
        'enrolled_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EnrollmentStatus::class,
            'source' => EnrollmentSource::class,
            'enrolled_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Une inscription donne acces si elle est active ET non echue.
     * `expires_at` a null signifie un acces a vie.
     */
    public function grantsAccess(): bool
    {
        if ($this->status !== EnrollmentStatus::Active) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /** @param  Builder<Enrollment>  $query */
    public function scopeGrantingAccess(Builder $query): void
    {
        $query->where('status', EnrollmentStatus::Active)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<AccessCode, $this> */
    public function accessCode(): BelongsTo
    {
        return $this->belongsTo(AccessCode::class);
    }
}
