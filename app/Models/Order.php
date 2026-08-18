<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Commande d'une formation (mode Commercial).
 *
 * @property OrderStatus $status
 * @property int $amount_fcfa
 * @property string $provider_ref
 * @property Carbon|null $paid_at
 */
class Order extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'course_id',
        'coupon_id',
        'amount_fcfa',
        'provider',
        'provider_ref',
        'status',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_fcfa' => 'integer',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
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

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
