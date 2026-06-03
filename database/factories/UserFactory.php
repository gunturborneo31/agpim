<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'opd_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::Opd,
            'title' => fake()->jobTitle(),
            'phone' => fake()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function opd(?int $opdId = null): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Opd,
            'opd_id' => $opdId,
        ]);
    }
}
