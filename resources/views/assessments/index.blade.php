@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h2">Assessments</h1>
            <p class="text-secondary">
                {{ auth()->user()->role === 'reviewer'
                    ? 'Assessments assigned to you for review.'
                    : 'Answer the NCSB questions directly in the system.' }}
            </p>
        </div>

        @if(auth()->user()->isAdmin() || auth()->user()->role === 'assessor')
            <form method="POST" action="{{ route('assessments.create') }}">
                @csrf
                <button class="btn btn-primary">New Assessment</button>
            </form>
        @endif
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Assessment</th>
                        @if(auth()->user()->isAdmin() || auth()->user()->role === 'reviewer')
                            <th>Assessor</th>
                        @endif
                        <th>Status</th>
                        <th>Score</th>
                        <th>Maturity</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assessments as $assessment)
                        <tr>
                            <td>#{{ $assessment->id }}</td>
                            @if(auth()->user()->isAdmin() || auth()->user()->role === 'reviewer')
                                <td>{{ $assessment->user->name }}</td>
                            @endif
                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ ucfirst(str_replace('_', ' ', $assessment->status)) }}
                                </span>
                            </td>
                            <td>
                                {{ $assessment->overall_score !== null
                                    ? number_format($assessment->overall_score * 100, 1).'%' : '—' }}
                            </td>
                            <td>{{ $assessment->overall_maturity_level ?? '—' }}</td>
                            <td>{{ $assessment->updated_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a
                                    class="btn btn-sm btn-outline-primary"
                                    href="{{ route('assessments.show', $assessment) }}"
                                >
                                    Open
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-secondary">No assessments yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
