@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between mb-4">
        <div>
            <h1 class="h2 mb-1">User management</h1>
            <p class="text-secondary mb-0">Assign Assessor, Reviewer, or Admin access.</p>
        </div>

        <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ ucfirst($user->role) }}</td>
                            <td class="text-end">
                                <form
                                    class="d-inline-flex gap-2"
                                    method="POST"
                                    action="{{ route('admin.users.role.update', $user) }}"
                                >
                                    @csrf
                                    @method('PATCH')
                                    <select class="form-select form-select-sm" name="role">
                                        @foreach($roles as $role)
                                            <option value="{{ $role }}" @selected($user->role === $role)>
                                                {{ ucfirst($role) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-primary">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
