<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Code promo appliqué à une commande (mode Commercial). Ce n'est PAS le mécanisme
 * d'accès (voir AccessCode) : il ne fait que réduire le montant d'une commande.
 *
 * @property int|null $discount_pct
 * @property int|null $discount_fcfa
 * @property int $max_uses
 * @property int $used_count
 * @property bool $is_active
 * @property Carbon|null $expires_at
 */
class Coupon extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'code',
        'discount_pct',
        'discount_fcfa',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'discount_pct' => 'integer',
            'discount_fcfa' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->used_count < $this->max_uses;
    }

    /**
     * Montant après remise (jamais négatif).
     */
    public function apply(int $amountFcfa): int
    {
        $discounted = $amountFcfa;

        if ($this->discount_pct !== null) {
            $discounted -= (int) round($amountFcfa * $this->discount_pct / 100);
        }

        if ($this->discount_fcfa !== null) {
            $discounted -= $this->discount_fcfa;
        }

        return max(0, $discounted);
    }
}
