<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AccessCodeRedemptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $redeemed_at
 */
class AccessCodeRedemption extends Model
{
    /** @use HasFactory<AccessCodeRedemptionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'access_code_id',
        'user_id',
        'redeemed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'redeemed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AccessCode, $this> */
    public function accessCode(): BelongsTo
    {
        return $this->belongsTo(AccessCode::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
