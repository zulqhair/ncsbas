@extends('layouts.app')
@section('title', 'Assessment responses #'.$assessment->id)
@section('content')
    <a class="breadcrumb-link" href="{{ route('reviews.show', $review) }}"><x-icon name="back" size="16" /> Review Assessment #{{ $assessment->id }}</a>
    <div class="page-heading">
        <div><p class="eyebrow">Review response record</p><h1>Assessment responses</h1><p>Assessment #{{ $assessment->id }} <span class="mx-2" aria-hidden="true">/</span> Assessor: {{ $assessment->user->name }}</p></div>
    </div>
    <div class="notice alert alert-info"><x-icon name="lock" /><div><strong>This response record is read only.</strong><p class="mb-0">Use the review workspace to add comments or record the review outcome.</p></div></div>
    <section aria-labelledby="responses-heading" class="mb-4">
        <div class="section-heading"><div><p class="eyebrow">Questionnaire record</p><h2 id="responses-heading">All assessment responses</h2></div></div>
        @foreach($elements as $number => $questions)
            <details class="element-card" open>
                <summary><span class="element-number">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span><span><small>{{ ucfirst(strtolower($questions->first()->domain)) }} / {{ $questions->first()->category }}</small><h3>{{ $questions->first()->element_name }}</h3></span></summary>
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
@endsection
