<?php

declare(strict_types=1);

namespace Modules\CompanyManagement\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Modules\CompanyManagement\Models\CompanyUser;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<CompanyUser>
 */
class CompanyUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CompanyUser>
     */
    protected $model = CompanyUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'), // password
        ];
    }
}
