@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <h1 class="display-5 fw-bold">
                National Cyber Security Baseline Assessment System
            </h1>

            <p class="lead text-secondary mt-3">
                Answer, review, and report NCSB v1.1 assessments in one place.
            </p>

            <div class="mt-4">
                <a class="btn btn-primary" href="{{ route('login') }}">Log in</a>
                <a class="btn btn-outline-primary" href="{{ route('register') }}">
                    Register as Assessor
                </a>
            </div>
        </div>
    </div>
@endsection
