@extends('layouts.app')
@section('title', 'User management')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Administration</p><h1>User management</h1><p>Assign Assessor, Reviewer, or Admin access.</p></div><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-primary" href="{{ route('admin.module-access.index') }}"><x-icon name="grid" /> Module access</a><a class="btn btn-outline-primary" href="{{ route('dashboard') }}"><x-icon name="back" /> Dashboard</a></div></div>
    <div class="notice alert alert-info"><x-icon name="users" /><div><strong>Access follows each user’s role.</strong><p class="mb-0">Assessors manage their assessments. Reviewers access assigned reviews. Administrators oversee the system.</p></div></div>
    <div class="card">
        <div class="table-toolbar"><h2>Registered users</h2><span>{{ $users->count() }} {{ $users->count() === 1 ? 'user' : 'users' }}</span></div>
        <div class="table-responsive" role="region" aria-label="User roles" tabindex="0">
            <table class="table align-middle"><thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Current role</th><th scope="col">Update access</th></tr></thead><tbody>
                @foreach($users as $user)
                    <tr><td class="fw-semibold">{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ ucfirst($user->role) }}</td><td>
                        <form class="role-form" method="POST" action="{{ route('admin.users.role.update', $user) }}" data-loading-form>
                            @csrf @method('PATCH')
                            <label class="visually-hidden" for="role-{{ $user->id }}">Role for {{ $user->name }}</label>
                            <select class="form-select" id="role-{{ $user->id }}" name="role">@foreach($roles as $role)<option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>@endforeach</select>
                            <button class="btn btn-sm btn-primary" type="submit" aria-label="Save role for {{ $user->name }}" data-loading-label="Saving…">Save</button>
                        </form>
                    </td></tr>
                @endforeach
            </tbody></table>
        </div>
    </div>
@endsection
