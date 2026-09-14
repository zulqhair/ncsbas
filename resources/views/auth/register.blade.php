@extends('layouts.app')
@section('title', 'Create an account')
@section('content')
    <div class="auth-layout">
        <aside class="hero-panel auth-aside" aria-label="About assessor access">
            <div><p class="eyebrow">Assessor registration</p><h2>Begin with a clear baseline.</h2><p>Create your account to assess cyber security practices against the NCSB questionnaire.</p>
                <ol class="auth-steps"><li><span>01</span> Create your Assessor account</li><li><span>02</span> Work through the baseline elements</li><li><span>03</span> Submit your assessment for review</li></ol>
            </div><p class="mb-0"><x-icon name="info" /> Reviewer and Admin roles are assigned by an administrator.</p>
        </aside>
        <section class="auth-form" aria-labelledby="register-heading">
            <p class="eyebrow">Get started</p><h1 id="register-heading">Create an account</h1><p class="text-secondary mb-4">New accounts are registered as Assessors.</p>
            <form method="POST" action="{{ route('register') }}" data-loading-form>
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="name">Full name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="255" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                    @error('name')<div class="invalid-feedback" id="name-error">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required maxlength="255" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<div class="invalid-feedback" id="email-error">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="new-password" required minlength="8" aria-describedby="password-help @error('password') password-error @enderror" @error('password') aria-invalid="true" @enderror>
                    <div class="form-text" id="password-help">Use at least 8 characters.</div>
                    @error('password')<div class="invalid-feedback" id="password-error">{{ $message }}</div>@enderror
                </div>
                <div class="mb-4"><label class="form-label" for="password_confirmation">Confirm password</label><input class="form-control" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required minlength="8"></div>
                <button class="btn btn-primary w-100" type="submit" data-loading-label="Creating account…">Register <x-icon name="arrow" /></button>
            </form>
            <p class="auth-switch">Already registered? <a href="{{ route('login') }}">Log in</a></p>
        </section>
    </div>
@endsection
