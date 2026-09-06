@extends('layouts.app')
@section('content')<h1 class="h2">Dashboard</h1><p class="text-secondary">Welcome, {{ auth()->user()->name }}.</p><div class="card shadow-sm"><div class="card-body"><h2 class="h5">Assessment workspace</h2><p class="mb-0 text-secondary">Assessment upload and result tracking will be added in the next milestone.</p></div></div>@endsection
