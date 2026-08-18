<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccessCode;
use App\Models\AccessCodeRedemption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccessCodeRedemption>
 */
class AccessCodeRedemptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_code_id' => AccessCode::factory(),
            'user_id' => User::factory()->learner(),
            'redeemed_at' => now(),
        ];
    }
}
