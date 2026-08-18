<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LessonType;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'position' => 1,
            'title' => fake()->sentence(4),
            'type' => LessonType::Video,
            'bunny_video_id' => fake()->uuid(),
            'content' => null,
            'asset_path' => null,
            'duration_seconds' => fake()->numberBetween(120, 1800),
            'is_preview' => false,
        ];
    }

    public function preview(): static
    {
        return $this->state(fn (array $attributes) => ['is_preview' => true]);
    }

    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LessonType::Text,
            'bunny_video_id' => null,
            'content' => fake()->paragraphs(4, true),
            'duration_seconds' => 0,
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LessonType::Pdf,
            'bunny_video_id' => null,
            'asset_path' => 'courses/'.fake()->uuid().'.pdf',
            'duration_seconds' => 0,
        ]);
    }

    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LessonType::Quiz,
            'bunny_video_id' => null,
            'duration_seconds' => 0,
        ]);
    }
}
