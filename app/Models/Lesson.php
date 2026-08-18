<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LessonType;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property LessonType $type
 * @property bool $is_preview
 * @property int $duration_seconds
 * @property string|null $bunny_video_id
 * @property string|null $video_path
 * @property string|null $asset_path
 */
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'module_id',
        'position',
        'title',
        'type',
        'bunny_video_id',
        'video_path',
        'content',
        'asset_path',
        'duration_seconds',
        'is_preview',
    ];

    /**
     * `bunny_video_id` est masque par defaut : il ne doit jamais se retrouver dans une
     * reponse JSON ni dans le HTML. Le player recoit une URL signee, generee cote serveur
     * apres autorisation (BunnySigner).
     *
     * @var list<string>
     */
    protected $hidden = [
        'bunny_video_id',
        'video_path',
        'asset_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'position' => 'integer',
            'duration_seconds' => 'integer',
            'is_preview' => 'boolean',
        ];
    }

    /** @return BelongsTo<Module, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /** @return HasOne<Quiz, $this> */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /** @return HasMany<LessonProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function course(): ?Course
    {
        return $this->module?->course;
    }
}
