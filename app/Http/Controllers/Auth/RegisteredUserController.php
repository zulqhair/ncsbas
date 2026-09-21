<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'email.unique' => 'Unable to create an account with those details.',
        ]);
        $user = User::create([...$validated, 'role' => User::ROLE_ASSESSOR]);
        Auth::login($user);
        $request->session()->regenerate();
        event(new Registered($user));

        return redirect()->route('verification.notice')->with('status', 'Your Assessor account has been created. Please verify your email address to continue.');
    }
}
