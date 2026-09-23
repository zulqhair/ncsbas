@extends('layouts.app')
@section('title', 'Assessments')
@section('content')
    <div class="page-heading">
        <div><p class="eyebrow">Assessor Module</p><h1>Assessments</h1><p>Manage your drafts, follow review progress, and access your results.</p></div>
        @if(auth()->user()->isAdmin() || auth()->user()->role === 'assessor')
            <form method="POST" action="{{ route('assessments.create') }}" data-loading-form>@csrf<button class="btn btn-primary" type="submit" data-loading-label="Creating draft…"><x-icon name="plus" /> New Assessment</button></form>
        @endif
    </div>
    <div class="card">
        <div class="table-toolbar"><h2>Assessment register</h2><span>{{ $assessments->count() }} {{ $assessments->count() === 1 ? 'assessment' : 'assessments' }}</span></div>
        @if($assessments->isEmpty())
            <div class="empty-state"><x-icon name="document" size="36" /><h2>No assessments yet.</h2><p>Start a new assessment to work through the NCSB baseline.</p></div>
        @else
            <div class="table-responsive" role="region" aria-label="Assessment register" tabindex="0">
                <table class="table align-middle"><thead><tr>
                    <th scope="col">No.</th>
                    <th scope="col">Status</th><th scope="col">Score</th><th scope="col">Maturity</th><th scope="col">Updated</th><th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr></thead><tbody>
                    @foreach($assessments as $assessment)
                        <tr><td><a class="table-link" href="{{ route('assessments.show', $assessment) }}">{{ $loop->iteration }}</a></td>
                            <td><x-status-badge :status="$assessment->status" /></td><td>{{ $assessment->overall_score !== null ? number_format($assessment->overall_score * 100, 1).'%' : '—' }}</td>
                            <td>{{ $assessment->overall_maturity_level ?? 'Not scored' }}</td><td class="text-nowrap"><time datetime="{{ $assessment->updated_at->toIso8601String() }}">{{ $assessment->updated_at->format('d M Y') }}</time></td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('assessments.show', $assessment) }}" aria-label="Open assessment {{ $assessment->id }}">Open <x-icon name="arrow" size="16" /></a></td>
                        </tr>
                    @endforeach
                </tbody></table>
            </div>
        @endif
    </div>
@endsection
