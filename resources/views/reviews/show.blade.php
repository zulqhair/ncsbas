@extends('layouts.app')
@section('title', 'Review assessment #'.$assessment->id)
@section('content')
    <a class="breadcrumb-link" href="{{ route('reviews.index') }}"><x-icon name="back" size="16" /> Reviewer Module</a>
    <div class="page-heading">
        <div><p class="eyebrow">Review workspace</p><h1>Review Assessment #{{ $assessment->id }}</h1><p>Assessor: {{ $assessment->user->name }} <span class="mx-2" aria-hidden="true">/</span> <x-status-badge :status="$review->status" /></p></div>
        <a class="btn btn-outline-primary" href="{{ route('assessments.report', $assessment) }}"><x-icon name="download" /> Download PDF</a>
    </div>
    <dl class="metrics metrics-three">
        <div class="metric"><dt>Overall score</dt><dd>{{ $assessment->overall_score !== null ? number_format($assessment->overall_score * 100, 1).'%' : 'Not calculated' }}</dd></div>
        <div class="metric"><dt>Overall maturity</dt><dd>{{ $assessment->overall_maturity_level ?? 'Not calculated' }}</dd></div>
        <div class="metric"><dt>Assigned reviewer</dt><dd>{{ $review->reviewer?->name ?? 'Unassigned' }}</dd></div>
    </dl>
    <section class="card mb-4" aria-labelledby="actions-heading"><div class="card-header"><h2 class="h5 mb-0" id="actions-heading">Review actions</h2></div><div class="card-body"><x-review-actions :review="$review" /></div></section>
    <section class="card mb-4" aria-labelledby="results-heading">
        <div class="card-header"><h2 class="h5 mb-0" id="results-heading">33 element results</h2><p class="text-secondary mb-0 mt-2">Maturity scores range from 0 (Initial) to 3 (Advanced).</p></div>
        <div class="table-responsive" role="region" aria-label="Element maturity results" tabindex="0"><table class="table"><thead><tr><th scope="col">Element</th><th scope="col">Yes answers</th><th scope="col">Score</th><th scope="col">Maturity</th></tr></thead><tbody>
            @for($i = 1; $i <= 33; $i++)
                @php($result = $results->get($i)) @php($element = $elements->get($i)?->first())
                <tr><th scope="row">{{ $i }}. {{ $result?->element_name ?? $element?->element_name ?? 'Element '.$i }}</th><td>{{ $result?->yes_count ?? 0 }}</td><td>{{ $result?->maturity_score ?? 0 }}/3</td><td><x-status-badge :status="$result?->maturity_level" /></td></tr>
            @endfor
        </tbody></table></div>
    </section>
    <section aria-labelledby="responses-heading" class="mb-4">
        <div class="section-heading"><div><p class="eyebrow">Questionnaire record</p><h2 id="responses-heading">Assessment responses</h2></div></div>
        @foreach($elements as $number => $questions)
            <details class="element-card" open><summary><span class="element-number">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span><span><small>{{ ucfirst(strtolower($questions->first()->domain)) }}</small><h3>{{ $questions->first()->element_name }}</h3></span></summary>
                <div class="element-questions">
                    @php($skipped = false)
                    @foreach($questions as $question)
                        <div class="response-row"><span><span class="question-number">Question {{ $question->number }}</span>{{ $question->question }}</span><strong>{{ $skipped ? 'Skipped' : ($answers[$question->id] ?? 'Not answered') }}</strong></div>
                        @if(($answers[$question->id] ?? null) === 'No') @php($skipped = true) @endif
                    @endforeach
                </div>
            </details>
        @endforeach
    </section>
    <section class="card" aria-labelledby="comments-heading"><div class="card-header"><h2 class="h5 mb-0" id="comments-heading">Comments</h2></div><div class="card-body"><x-review-comments :review="$review" /></div></section>
@endsection
