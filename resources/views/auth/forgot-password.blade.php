<div>
    <!-- Simplicity is an acquired taste. - Katharine Gerould -->
</div>
@extends('layouts.app')
@section('title', 'Reset password')
@section('content')
    <section class="auth-form mx-auto" aria-labelledby="forgot-password-heading">
        <p class="eyebrow">Account recovery</p><h1 id="forgot-password-heading">Reset your password</h1>
        <p class="text-secondary mb-4">Enter your email address and we will send a reset link if an account matches it.</p>
        <form method="POST" action="{{ route('password.email') }}" data-loading-form>
            @csrf
            <div class="mb-4">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<div class="invalid-feedback" id="email-error">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary w-100" type="submit" data-loading-label="Sending…">Send reset link <x-icon name="arrow" /></button>
        </form>
        <p class="auth-switch"><a href="{{ route('login') }}">Return to log in</a></p>
    </section>
@endsection
