<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'title' => $title,
            'subtitle' => fake()->sentence(8),
            'description' => fake()->paragraphs(3, true),
            'cover_path' => null,
            'price_fcfa' => fake()->numberBetween(25, 300) * 1000,
            'level' => fake()->randomElement(CourseLevel::cases()),
            'status' => CourseStatus::Draft,
            'instructor_id' => User::factory()->instructor(),
            'duration_minutes' => fake()->numberBetween(60, 900),
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Archived,
        ]);
    }
}
