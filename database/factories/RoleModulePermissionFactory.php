<?php

namespace Database\Factories;

use App\Models\RoleModulePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleModulePermission>
 */
class RoleModulePermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role' => fake()->randomElement([User::ROLE_ASSESSOR, User::ROLE_REVIEWER]),
            'module' => fake()->randomElement(array_keys(RoleModulePermission::modules())),
        ];
    }
}
