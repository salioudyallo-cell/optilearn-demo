<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccessCode;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessCode>
 */
class AccessCodeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => AccessCode::generateUniqueCode(),
            'course_id' => Course::factory(),
            'max_uses' => 1,
            'used_count' => 0,
            'expires_at' => null,
            'is_active' => true,
            'label' => 'Cohorte '.fake()->word().' — '.fake()->company(),
            'created_by' => User::factory()->admin(),
        ];
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_uses' => 1,
            'used_count' => 1,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDay(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
