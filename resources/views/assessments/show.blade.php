@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
    <div>
        <h1 class="h2 mb-1">Assessment #{{ $assessment->id }}</h1>
        <p class="text-secondary mb-0">
            Status:
            <span class="badge text-bg-secondary">
                {{ ucfirst(str_replace('_', ' ', $assessment->status)) }}
            </span>
        </p>
    </div>

    <a class="btn btn-outline-primary" href="{{ route('assessments.report', $assessment) }}">
        Download PDF
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Please review the questionnaire sequence.</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($assessment->overall_maturity_level)
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Overall score</div>
                    <div class="display-6">
                        {{ number_format(($assessment->overall_score ?? 0) * 100, 1) }}%
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Maturity level</div>
                    <div class="display-6">{{ $assessment->overall_maturity_level }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="text-secondary small">Elements scored</div>
                    <div class="display-6">{{ $results->count() }} / 33</div>
                </div>
            </div>
        </div>
    </div>
@endif

@if($canEdit)
    <div class="alert alert-info">
        Answer each element in order. Selecting <strong>No</strong> skips and locks the remaining questions in that element.
    </div>

    <form method="POST" action="{{ route('assessments.save', $assessment) }}" data-sequential-questionnaire>
        @csrf
        @method('PUT')
@endif

@foreach($elements as $number => $questions)
    <div class="card mb-3">
        <div class="card-header">
            <strong>{{ $number }}. {{ $questions->first()->element_name }}</strong>
        </div>
        <div class="card-body">
            @foreach($questions as $question)
                <div
                    class="border-bottom pb-3 mb-3 questionnaire-row"
                    data-questionnaire-row
                    data-element="{{ $number }}"
                >
                    <p class="mb-2">{{ $question->number }}. {{ $question->question }}</p>
                    <label class="me-3">
                        <input
                            class="questionnaire-input"
                            type="radio"
                            name="answers[{{ $question->id }}]"
                            value="Yes"
                            @checked(($answers[$question->id] ?? null) === 'Yes')
                            @disabled(!$canEdit)
                        >
                        Yes
                    </label>
                    <label>
                        <input
                            class="questionnaire-input"
                            type="radio"
                            name="answers[{{ $question->id }}]"
                            value="No"
                            @checked(($answers[$question->id] ?? null) === 'No')
                            @disabled(!$canEdit)
                        >
                        No
                    </label>
                    <div class="small text-secondary mt-2 d-none" data-questionnaire-note></div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

@if($canEdit)
        <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
            <button class="btn btn-primary" type="submit">Save Draft and Recalculate</button>
            <button
                class="btn btn-success"
                type="button"
                data-submit-assessment
                data-bs-toggle="modal"
                data-bs-target="#submitAssessmentModal"
                disabled
            >
                Submit Assessment
            </button>
            <span class="small text-secondary" data-submission-progress>
                0/{{ $elements->count() }} elements complete
            </span>
        </div>
    </form>
@endif

@if($canEdit)
    <div class="modal fade" id="submitAssessmentModal" tabindex="-1" aria-labelledby="submitAssessmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('reviews.request', $assessment) }}">
                    @csrf
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="submitAssessmentModalLabel">Submit assessment for review</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-secondary">
                            Select a reviewer. After submission, the assessment will be locked for editing.
                        </p>
                        <label class="form-label" for="reviewer_id">Reviewer</label>
                        <select class="form-select" id="reviewer_id" name="reviewer_id" required>
                            <option value="">Choose reviewer</option>
                            @foreach($reviewers as $reviewer)
                                <option value="{{ $reviewer->id }}">
                                    {{ $reviewer->name }} ({{ ucfirst($reviewer->role) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Submit for review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@foreach($assessment->reviews as $review)
    <div class="card mb-3">
        <div class="card-header d-flex flex-wrap justify-content-between gap-2">
            <strong>Review by {{ $review->reviewer?->name ?? 'Unassigned' }}</strong>
            <span class="badge text-bg-info">{{ ucfirst($review->status) }}</span>
        </div>
        <div class="card-body">
            @if($review->decline_reason)
                <p class="text-danger">Decline reason: {{ $review->decline_reason }}</p>
            @endif

            @if(auth()->id() === $review->reviewer_id || auth()->user()->isAdmin())
                <div class="d-flex gap-2 mb-3">
                    <form method="POST" action="{{ route('reviews.update', $review) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="accept">
                        <button class="btn btn-sm btn-success">Accept</button>
                    </form>
                    <form method="POST" action="{{ route('reviews.update', $review) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="complete">
                        <button class="btn btn-sm btn-primary">Mark completed</button>
                    </form>
                    <form class="d-flex flex-wrap gap-1" method="POST" action="{{ route('reviews.update', $review) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="action" value="decline">
                        <input
                            class="form-control form-control-sm"
                            name="decline_reason"
                            placeholder="Reason (optional)"
                        >
                        <button class="btn btn-sm btn-outline-danger">Decline</button>
                    </form>
                </div>
            @endif

            <div class="mb-3">
                @forelse($review->comments as $comment)
                    <div class="border rounded p-2 mb-2">
                        <strong>{{ $comment->user->name }}</strong>
                        <small class="text-secondary">{{ $comment->created_at->format('Y-m-d H:i') }}</small>
                        <div>{{ $comment->body }}</div>
                    </div>
                @empty
                    <p class="text-secondary">No comments yet.</p>
                @endforelse
            </div>

            @if(auth()->id() === $review->reviewer_id
                || auth()->user()->isAdmin()
                || auth()->id() === $assessment->user_id)
                <form class="d-flex flex-wrap gap-2" method="POST" action="{{ route('reviews.comments', $review) }}">
                    @csrf
                    <input class="form-control flex-grow-1" name="body" placeholder="Leave a comment" required>
                    <button class="btn btn-outline-secondary">Comment</button>
                </form>
            @endif
        </div>
    </div>
@endforeach
@endsection
