@extends('layouts.app')
@section('title', 'Log in')
@section('content')
    <div class="auth-layout">
        <aside class="hero-panel auth-aside" aria-label="About your workspace">
            <div><p class="eyebrow">NCSBAS workspace</p><h2>Your baseline.<br>A shared view.</h2><p>Continue your assessment, review assigned responses, or oversee progress across the system.</p>
                <ol class="auth-steps"><li><span>01</span> Assess your cyber security practices</li><li><span>02</span> Review responses and share feedback</li><li><span>03</span> Report your maturity results</li></ol>
            </div>
            <p class="mb-0"><x-icon name="shield" /> National Cyber Security Baseline v1.1</p>
        </aside>
        <section class="auth-form" aria-labelledby="login-heading">
            <p class="eyebrow">Welcome back</p><h1 id="login-heading">Log in</h1><p class="text-secondary mb-4">Enter your details to access your workspace.</p>
            <form method="POST" action="{{ route('login') }}" data-loading-form>
                @csrf
                <div class="mb-4">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')<div class="invalid-feedback" id="email-error">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control @error('password') is-invalid @enderror" id="password" type="password" name="password" autocomplete="current-password" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password')<div class="invalid-feedback" id="password-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-4"><input class="form-check-input" id="remember" type="checkbox" name="remember" @checked(old('remember'))><label class="form-check-label" for="remember">Remember me</label></div>
                <button class="btn btn-primary w-100" type="submit" data-loading-label="Logging in…">Log in <x-icon name="arrow" /></button>
            </form>
            <p class="auth-switch"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
            <p class="auth-switch">New to NCSBAS? <a href="{{ route('register') }}">Create an account</a></p>
        </section>
    </div>
@endsection
