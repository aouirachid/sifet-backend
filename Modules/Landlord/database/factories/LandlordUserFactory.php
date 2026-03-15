<?php

declare(strict_types=1);

namespace Modules\Landlord\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Modules\Landlord\Models\LandlordUser;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\Landlord\Models\LandlordUser>
 */
class LandlordUserFactory extends Factory
{
    /**
     * The default password for testing (hashed).
     */
    protected static ?string $password = null;

    /**
     * The current model being used by the factory.
     *
     * @var string
     */
    protected $model = LandlordUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => []);
    }
}
