@extends('layouts.app')
@section('title', 'Assessment #'.$assessment->id)
@section('content')
    @php
        $displayAnswers = $canEdit && is_array(old('answers')) ? old('answers') : $answers;
        $workflow = ['draft' => 'Draft', 'open' => 'Awaiting review', 'in_review' => 'In review', 'completed' => 'Completed'];
    @endphp
    <a class="breadcrumb-link" href="{{ route('assessments.index') }}"><x-icon name="back" size="16" /> Assessments</a>
    <div class="page-heading">
        <div><p class="eyebrow">NCSB v1.1 · Questionnaire</p><h1>Assessment #{{ $assessment->id }}</h1><p>{{ $assessment->user->name }} <span class="mx-2" aria-hidden="true">/</span> <x-status-badge :status="$assessment->status" /></p></div>
        <a class="btn btn-outline-primary" href="{{ route('assessments.report', $assessment) }}"><x-icon name="download" /> Download PDF</a>
    </div>
    <ol class="workflow-strip" aria-label="Assessment workflow">
        @foreach($workflow as $state => $label)<li @if($assessment->status === $state) aria-current="step" @endif><span>0{{ $loop->iteration }}</span>{{ $label }}</li>@endforeach
    </ol>
    @if($assessment->overall_maturity_level)
        <dl class="metrics metrics-three">
            <div class="metric"><dt>Overall score</dt><dd>{{ number_format(($assessment->overall_score ?? 0) * 100, 1) }}%</dd></div>
            <div class="metric"><dt>Maturity level</dt><dd>{{ $assessment->overall_maturity_level }}</dd></div>
            <div class="metric"><dt>Elements scored</dt><dd>{{ $results->count() }} / 33</dd></div>
        </dl>
    @endif
    <div class="notice alert alert-info"><x-icon :name="$canEdit ? 'info' : 'lock'" /><div>
        @if($canEdit)<strong>Work through each element in sequence.</strong><p class="mb-0">Selecting <strong>No</strong> skips the remaining questions in that element. Save your draft before submitting it for review.</p>
        @else<strong>This assessment is read only.</strong><p class="mb-0">{{ $assessment->status === 'draft' ? 'Only the assessor or an administrator can edit this draft.' : 'Responses are locked after submission. You can view the answers, review comments, and download the report.' }}</p>@endif
    </div></div>
    <div class="assessment-layout">
        <aside class="element-navigation">
            <details open><summary>Assessment elements <span class="text-secondary">({{ $elements->count() }})</span></summary>
                <nav aria-label="Assessment elements"><ol>@foreach($elements as $number => $questions)<li><a href="#element-{{ $number }}" data-element-link><span class="element-index">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span><span>{{ $questions->first()->element_name }}</span></a></li>@endforeach</ol></nav>
            </details>
        </aside>
        <div>
            @if($canEdit)<form method="POST" action="{{ route('assessments.save', $assessment) }}" data-sequential-questionnaire data-loading-form data-restored-input="{{ session()->hasOldInput('answers') ? 'true' : 'false' }}">@csrf @method('PUT')@endif
            @forelse($elements as $number => $questions)
                <details class="element-card" id="element-{{ $number }}" open>
                    <summary><span class="element-number">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span><span><small>{{ ucfirst(strtolower($questions->first()->domain)) }} / {{ $questions->first()->category }}</small><h2>{{ $questions->first()->element_name }}</h2></span></summary>
                    <div class="element-questions">
                        @php($skipped = false)
                        @foreach($questions as $question)
                            <fieldset class="questionnaire-row" data-questionnaire-row data-element="{{ $number }}" aria-describedby="question-note-{{ $question->id }}">
                                <legend><span class="question-number">Question {{ $question->number }}</span>{{ $question->question }}</legend>
                                @if($canEdit)
                                    <div class="answer-options">
                                        @foreach(['Yes', 'No'] as $answer)
                                            <label class="answer-option"><input class="questionnaire-input" type="radio" name="answers[{{ $question->id }}]" value="{{ $answer }}" @checked(($displayAnswers[$question->id] ?? null) === $answer)> {{ $answer }}</label>
                                        @endforeach
                                    </div>
                                @else
                                    <strong>{{ $skipped ? 'Skipped' : ($answers[$question->id] ?? 'Not answered') }}</strong>
                                @endif
                                <div class="questionnaire-note" id="question-note-{{ $question->id }}" data-questionnaire-note @if(!$skipped) hidden @endif>{{ $skipped ? 'An earlier question in this element was answered No.' : '' }}</div>
                            </fieldset>
                            @if(($answers[$question->id] ?? null) === 'No') @php($skipped = true) @endif
                        @endforeach
                    </div>
                </details>
            @empty
                <div class="card empty-state"><x-icon name="document" size="32" /><h2>The questionnaire is not available yet.</h2><p>An administrator needs to import the baseline questions before you can begin.</p></div>
            @endforelse
            @if($canEdit)
                <div class="save-dock">
                    <div><strong data-save-state role="status">Draft workspace</strong><p class="text-secondary" data-submission-progress>Save your answers to update your results.</p></div>
                    <button class="btn btn-primary" type="submit" data-loading-label="Saving draft…">Save Draft and Recalculate</button>
                </div>
                <noscript><p class="mt-3 text-secondary">Answer questions in sequence and leave the remaining questions blank after a No. Save your draft, then use the review form below.</p></noscript>
                </form>
            @endif
            @if($canAssignReview && in_array($assessment->status, ['draft', 'open'], true))
                <section class="card review-submission" aria-labelledby="submission-heading">
                    <div class="card-body">
                        <h2 id="submission-heading">{{ $assessment->status === 'open' ? 'Assign a reviewer' : 'Submit assessment for review' }}</h2>
                        <p class="text-secondary" id="submission-help">{{ $assessment->status === 'open' ? 'Choose a reviewer to take this assessment forward. The responses will remain locked.' : 'Complete and save every element, then choose a reviewer. Submission locks your responses for editing.' }}</p>
                        <p data-submit-guidance role="status" class="text-secondary"></p>
                        <form method="POST" action="{{ route('reviews.request', $assessment) }}" data-review-submission data-loading-form>
                            @csrf
                            <div class="review-submission-row">
                                <div><label class="form-label" for="reviewer_id">Reviewer</label><select class="form-select" id="reviewer_id" name="reviewer_id" required aria-describedby="submission-help"><option value="">Choose reviewer</option>@foreach($reviewers as $reviewer)<option value="{{ $reviewer->id }}" @selected(old('reviewer_id') == $reviewer->id)>{{ $reviewer->name }} ({{ ucfirst($reviewer->role) }})</option>@endforeach</select></div>
                                <button class="btn btn-primary" type="submit" @if($canEdit) data-submit-assessment @endif data-loading-label="Submitting…">{{ $assessment->status === 'open' ? 'Assign reviewer' : 'Submit Assessment' }} <x-icon name="arrow" /></button>
                            </div>
                        </form>
                        @if($reviewers->isEmpty())<p class="text-secondary mt-3 mb-0">No reviewers are available. An administrator must assign Reviewer access to a user first.</p>@endif
                    </div>
                </section>
            @endif
            @foreach($assessment->reviews as $review)
                <section class="card mt-4" aria-labelledby="review-heading-{{ $review->id }}">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2"><h2 class="h5 mb-0" id="review-heading-{{ $review->id }}">Review by {{ $review->reviewer?->name ?? 'Unassigned' }}</h2><x-status-badge :status="$review->status" /></div>
                    <div class="card-body">
                        @if(auth()->id() === $review->reviewer_id || auth()->user()->isAdmin())<div class="mb-4"><x-review-actions :review="$review" /></div>
                        @elseif($review->decline_reason)<p class="text-secondary">Decline reason: {{ $review->decline_reason }}</p>@endif
                        <h3 class="mb-3">Comments</h3><x-review-comments :review="$review" :can-comment="auth()->id() === $review->reviewer_id || auth()->user()->isAdmin() || auth()->id() === $assessment->user_id" />
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection
