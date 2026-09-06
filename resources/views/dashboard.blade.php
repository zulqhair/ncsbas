@extends('layouts.app')
@section('content')<h1 class="h2">Dashboard</h1><p class="text-secondary">Welcome, {{ auth()->user()->name }}.</p><a class="btn btn-primary" href="{{ route('assessments.index') }}">Open Assessor Module</a>@endsection
