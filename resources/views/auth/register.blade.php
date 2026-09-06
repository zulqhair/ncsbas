@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-2">Create an account</h1>
                    <p class="text-secondary">New accounts are registered as Assessors.</p>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="name">Name</label>
                            <input
                                class="form-control @error('name') is-invalid @enderror"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autofocus
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Email</label>
                            <input
                                class="form-control @error('email') is-invalid @enderror"
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <input
                                class="form-control @error('password') is-invalid @enderror"
                                id="password"
                                type="password"
                                name="password"
                                required
                            >
                            <div class="form-text">Use at least 8 characters.</div>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label" for="password_confirmation">Confirm password</label>
                            <input
                                class="form-control"
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                required
                            >
                        </div>

                        <button class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
