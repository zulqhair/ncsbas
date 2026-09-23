<?php

namespace Database\Seeders;

use App\Models\RoleModulePermission;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleModulePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            [User::ROLE_ASSESSOR, RoleModulePermission::MODULE_ASSESSOR],
            [User::ROLE_REVIEWER, RoleModulePermission::MODULE_ASSESSOR],
            [User::ROLE_REVIEWER, RoleModulePermission::MODULE_REVIEWER],
        ] as [$role, $module]) {
            RoleModulePermission::query()->updateOrCreate(
                compact('role', 'module'),
            );
        }
    }
}
