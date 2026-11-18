<?php

namespace Database\Factories;

use App\Models\PasswordResetLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PasswordResetLink>
 */
class PasswordResetLinkFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainToken = Str::random(64);

        return [
            'user_id' => User::factory(),
            'creator_id' => User::factory(),
            'token' => $plainToken,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHours(max(1, (int) config('invites.password_reset_lifetime_hours'))),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
