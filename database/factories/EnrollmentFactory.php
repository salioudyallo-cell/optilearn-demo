<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EnrollmentSource;
use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->learner(),
            'course_id' => Course::factory(),
            'status' => EnrollmentStatus::Active,
            'source' => EnrollmentSource::Manual,
            'access_code_id' => null,
            'order_id' => null,
            'enrolled_at' => now(),
            'expires_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => ['status' => EnrollmentStatus::Revoked]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EnrollmentStatus::Expired,
            'expires_at' => now()->subDay(),
        ]);
    }
}
