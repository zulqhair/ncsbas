<div>
    <!-- No surplus words or unnecessary actions. - Marcus Aurelius -->
</div>
@extends('layouts.app')
@section('title', 'Choose a new password')
@section('content')
    <section class="auth-form mx-auto" aria-labelledby="reset-password-heading">
        <p class="eyebrow">Account recovery</p><h1 id="reset-password-heading">Choose a new password</h1>
        <form method="POST" action="{{ route('password.update') }}" data-loading-form>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="mb-3">
                <label class="form-label" for="email">Email address</label>
                <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required maxlength="255" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email')<div class="invalid-feedback" id="email-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label" for="password">New password</label>
                <input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password" required minlength="15" maxlength="128" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <div class="form-text">Use a passphrase of 15 to 128 characters.</div>
                @error('password')<div class="invalid-feedback" id="password-error">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4"><label class="form-label" for="password_confirmation">Confirm new password</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required minlength="15" maxlength="128"></div>
            <button class="btn btn-primary w-100" type="submit" data-loading-label="Resetting…">Reset password <x-icon name="arrow" /></button>
        </form>
    </section>
@endsection
