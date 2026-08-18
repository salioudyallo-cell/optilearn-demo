<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizAttemptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property array<int, int> $answers
 * @property int $score_pct
 * @property bool $passed
 * @property Carbon $attempted_at
 */
class QuizAttempt extends Model
{
    /** @use HasFactory<QuizAttemptFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'quiz_id',
        'score_pct',
        'passed',
        'answers',
        'attempted_at',
    ];

    /**
     * `answers` est lu et ecrit uniquement par ce cast. Sur MariaDB le type json n'est
     * qu'un alias de LONGTEXT : toute interrogation SQL de ce contenu est interdite
     * (regle de portabilite n°2).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score_pct' => 'integer',
            'passed' => 'boolean',
            'answers' => 'array',
            'attempted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
