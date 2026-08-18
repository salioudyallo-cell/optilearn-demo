<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificate>
 */
class CertificateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->learner(),
            'course_id' => Course::factory()->published(),
            'serial' => sprintf('OPT-%s-%s', now()->year, strtoupper(fake()->unique()->bothify('??####'))),
            'issued_at' => now(),
            'pdf_path' => null,
        ];
    }
}
