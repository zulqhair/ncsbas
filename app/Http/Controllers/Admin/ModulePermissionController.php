<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleModulePermission;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModulePermissionController extends Controller
{
    public function index(): View
    {
        $roles = [User::ROLE_ASSESSOR, User::ROLE_REVIEWER];
        $permissions = array_fill_keys($roles, []);

        RoleModulePermission::query()
            ->whereIn('role', $roles)
            ->get()
            ->each(function (RoleModulePermission $permission) use (&$permissions): void {
                $permissions[$permission->role][$permission->module] = true;
            });

        return view('admin.module-access', [
            'roles' => $roles,
            'modules' => RoleModulePermission::modules(),
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $roles = [User::ROLE_ASSESSOR, User::ROLE_REVIEWER];
        $modules = array_keys(RoleModulePermission::modules());
        $validated = $request->validate([
            'modules' => ['nullable', 'array:'.implode(',', $roles)],
            'modules.*' => ['array'],
            'modules.*.*' => ['string', Rule::in($modules)],
        ]);
        $permissions = [];

        foreach ($roles as $role) {
            foreach (array_unique($validated['modules'][$role] ?? []) as $module) {
                $permissions[] = [
                    'role' => $role,
                    'module' => $module,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($roles, $permissions): void {
            RoleModulePermission::query()->whereIn('role', $roles)->delete();

            if ($permissions !== []) {
                RoleModulePermission::query()->insert($permissions);
            }
        });

        return back()->with('status', 'Module access has been updated.');
    }
}
