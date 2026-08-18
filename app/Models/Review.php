<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $rating
 * @property string|null $comment
 */
class Review extends Model
{
    /** @var list<string> */
    protected $fillable = ['user_id', 'course_id', 'rating', 'comment'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['rating' => 'integer'];
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
}
