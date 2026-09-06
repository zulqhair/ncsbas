@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
        <div>
            <a class="text-decoration-none" href="{{ route('reviews.index') }}">&larr; Reviewer Module</a>
            <h1 class="h2 mt-2 mb-1">Review Assessment #{{ $assessment->id }}</h1>
            <p class="text-secondary mb-0">
                Assessor: {{ $assessment->user->name }}
                <span class="ms-2 badge text-bg-info">{{ ucfirst($review->status) }}</span>
            </p>
        </div>

        <a class="btn btn-outline-primary" href="{{ route('assessments.report', $assessment) }}">
            Download PDF
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Overall score</div>
                    <div class="display-6">
                        {{ $assessment->overall_score !== null
                            ? number_format($assessment->overall_score * 100, 1).'%' : 'Not calculated' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Overall maturity</div>
                    <div class="display-6">{{ $assessment->overall_maturity_level ?? 'Not calculated' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Assigned reviewer</div>
                    <div class="fs-4">{{ $review->reviewer?->name ?? 'Unassigned' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Review actions</strong></div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('reviews.update', $review) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="accept">
                    <button class="btn btn-success" {{ $review->status === 'completed' ? 'disabled' : '' }}>
                        Accept review
                    </button>
                </form>
                <form method="POST" action="{{ route('reviews.update', $review) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="complete">
                    <button class="btn btn-primary" {{ $review->status !== 'accepted' ? 'disabled' : '' }}>
                        Mark completed
                    </button>
                </form>
                <form class="d-flex flex-wrap gap-2" method="POST" action="{{ route('reviews.update', $review) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="decline">
                    <input class="form-control" name="decline_reason" placeholder="Reason (optional)">
                    <button class="btn btn-outline-danger" {{ $review->status === 'completed' ? 'disabled' : '' }}>
                        Decline
                    </button>
                </form>
            </div>
            @if($review->decline_reason)
                <p class="text-danger mt-3 mb-0">Decline reason: {{ $review->decline_reason }}</p>
            @endif
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>33 element results</strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Element</th>
                        <th>Yes answers</th>
                        <th>Score</th>
                        <th>Maturity</th>
                    </tr>
                </thead>
                <tbody>
                    @for($i = 1; $i <= 33; $i++)
                        @php($result = $results->get($i))
                        @php($element = $elements->get($i)?->first())
                        <tr>
                            <td>{{ $i }}. {{ $result?->element_name ?? $element?->element_name ?? 'Element '.$i }}</td>
                            <td>{{ $result?->yes_count ?? 0 }}</td>
                            <td>{{ $result?->maturity_score ?? 0 }}/3</td>
                            <td>{{ $result?->maturity_level ?? 'Not scored' }}</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Assessment responses</strong></div>
        <div class="card-body">
            @foreach($elements as $number => $questions)
                <div class="border-bottom pb-3 mb-3">
                    <h2 class="h6">{{ $number }}. {{ $questions->first()->element_name }}</h2>
                    @foreach($questions as $question)
                        <div class="d-flex justify-content-between gap-3 py-2 response-row">
                            <span class="text-break">{{ $question->number }}. {{ $question->question }}</span>
                            <strong>{{ $answers[$question->id] ?? 'Not answered' }}</strong>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Comments</strong></div>
        <div class="card-body">
            @forelse($review->comments as $comment)
                <div class="border rounded p-2 mb-2">
                    <strong>{{ $comment->user->name }}</strong>
                    <small class="text-secondary">{{ $comment->created_at->format('Y-m-d H:i') }}</small>
                    <div>{{ $comment->body }}</div>
                </div>
            @empty
                <p class="text-secondary">No comments yet.</p>
            @endforelse

            <form class="d-flex flex-wrap gap-2 mt-3" method="POST" action="{{ route('reviews.comments', $review) }}">
                @csrf
                <input class="form-control flex-grow-1" name="body" placeholder="Leave a review comment" required>
                <button class="btn btn-outline-secondary">Comment</button>
            </form>
        </div>
    </div>
@endsection
