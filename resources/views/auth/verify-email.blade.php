<div>
    <!-- Live as if you were to die tomorrow. Learn as if you were to live forever. - Mahatma Gandhi -->
</div>
@extends('layouts.app')
@section('title', 'Verify your email address')
@section('content')
    <section class="auth-form mx-auto" aria-labelledby="verify-email-heading">
        <p class="eyebrow">Account security</p><h1 id="verify-email-heading">Verify your email address</h1>
        <p class="text-secondary mb-4">We sent a verification link to your registered email address. Open it to access your assessment workspace.</p>
        <form method="POST" action="{{ route('verification.send') }}" data-loading-form>
            @csrf
            <button class="btn btn-primary w-100" type="submit" data-loading-label="Sending…">Resend verification link <x-icon name="arrow" /></button>
        </form>
        <form class="mt-3 text-center" method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-link" type="submit">Log out</button></form>
    </section>
@endsection
