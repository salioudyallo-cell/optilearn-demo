<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\QuizOptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizOption extends Model
{
    /** @use HasFactory<QuizOptionFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'quiz_question_id',
        'label',
        'is_correct',
    ];

    /**
     * `is_correct` est masque : la bonne reponse ne doit pas fuiter vers le client
     * avant la correction.
     *
     * @var list<string>
     */
    protected $hidden = [
        'is_correct',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    /** @return BelongsTo<QuizQuestion, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'quiz_question_id');
    }
}
