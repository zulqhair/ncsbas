@extends('layouts.app')
@section('title', 'Module access')
@section('content')
    <div class="page-heading"><div><p class="eyebrow">Administration</p><h1>Module access</h1><p>Choose which workspaces each role can open.</p></div><a class="btn btn-outline-primary" href="{{ route('admin.users.index') }}"><x-icon name="back" /> User management</a></div>
    <div class="notice alert alert-info"><x-icon name="info" /><div><strong>Changes take effect immediately.</strong><p class="mb-0">Module access controls both navigation links and direct module URLs. Administrators always retain access to every module.</p></div></div>
    <form method="POST" action="{{ route('admin.module-access.update') }}" data-loading-form>
        @csrf
        @method('PUT')
        <div class="card">
            <div class="table-toolbar"><h2>Role permissions</h2><span>Select the modules each role can access</span></div>
            <div class="table-responsive" role="region" aria-label="Module access permissions" tabindex="0">
                <table class="table align-middle"><thead><tr><th scope="col">Module</th>@foreach($roles as $role)<th scope="col">{{ ucfirst($role) }}</th>@endforeach</tr></thead><tbody>
                    @foreach($modules as $module => $label)
                        <tr>
                            <th scope="row">{{ $label }}</th>
                            @foreach($roles as $role)
                                <td>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="module-{{ $role }}-{{ $module }}" name="modules[{{ $role }}][]" value="{{ $module }}" @checked($permissions[$role][$module] ?? false)>
                                        <label class="form-check-label" for="module-{{ $role }}-{{ $module }}"><span class="visually-hidden">Allow {{ ucfirst($role) }} to access {{ $label }}</span></label>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody></table>
            </div>
            <div class="card-body border-top"><button class="btn btn-primary" type="submit" data-loading-label="Saving…"><x-icon name="check" /> Save module access</button></div>
        </div>
    </form>
@endsection
