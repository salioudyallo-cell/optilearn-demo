<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccessCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $max_uses
 * @property int $used_count
 * @property bool $is_active
 * @property Carbon|null $expires_at
 */
class AccessCode extends Model
{
    /** @use HasFactory<AccessCodeFactory> */
    use HasFactory;

    /**
     * Alphabet sans ambiguite visuelle : ni O/0, ni I/1/L. Un code se transmet souvent
     * par telephone ou par WhatsApp, la confusion de caracteres est le premier motif
     * d'echec d'activation.
     */
    public const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public const LENGTH = 10;

    /** @var list<string> */
    protected $fillable = [
        'code',
        'course_id',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
        'label',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Genere un code aleatoire non ambigu. `random_bytes` via Str::random n'est pas
     * utilise ici car il faut contraindre l'alphabet.
     */
    public static function generateCode(): string
    {
        $alphabetLength = strlen(self::ALPHABET);
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $alphabetLength - 1)];
        }

        return $code;
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = self::generateCode();
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function normalizeCode(string $code): string
    {
        return Str::upper(trim($code));
    }

    public function remainingUses(): int
    {
        return max(0, $this->max_uses - $this->used_count);
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRedeemable(): bool
    {
        return $this->is_active && ! $this->hasExpired() && $this->remainingUses() > 0;
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AccessCodeRedemption, $this> */
    public function redemptions(): HasMany
    {
        return $this->hasMany(AccessCodeRedemption::class);
    }

    /** @return HasMany<Enrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }
}
