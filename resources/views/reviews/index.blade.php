@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h2 mb-1">Reviewer Module</h1>
            <p class="text-secondary mb-0">
                Review assignments, assessment results, comments, and completion status.
            </p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Assessment</th>
                        <th>Assessor</th>
                        <th>Reviewer</th>
                        <th>Status</th>
                        <th>Overall maturity</th>
                        <th>Updated</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td>#{{ $review->assessment->id }}</td>
                            <td>{{ $review->assessment->user->name }}</td>
                            <td>{{ $review->reviewer?->name ?? 'Unassigned' }}</td>
                            <td>
                                <span class="badge text-bg-info">
                                    {{ ucfirst($review->status) }}
                                </span>
                            </td>
                            <td>{{ $review->assessment->overall_maturity_level ?? 'Not calculated' }}</td>
                            <td>{{ $review->updated_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('reviews.show', $review) }}">
                                    Open review
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-secondary">
                                No review assignments are available.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
