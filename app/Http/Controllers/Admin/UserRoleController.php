<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserRoleController extends Controller
{
    public function index(): View
    {
        return view('admin.users', [
            'users' => User::query()->orderBy('name')->get(),
            'roles' => [User::ROLE_ASSESSOR, User::ROLE_REVIEWER, User::ROLE_ADMIN],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => [
                'required',
                Rule::in([User::ROLE_ASSESSOR, User::ROLE_REVIEWER, User::ROLE_ADMIN]),
            ],
        ]);
        $user->update($validated);

        return back()->with('status', "{$user->name}'s role has been updated.");
    }
}
