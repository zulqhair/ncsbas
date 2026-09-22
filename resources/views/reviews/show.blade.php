@extends('layouts.app')
@section('title', 'Review assessment #'.$assessment->id)
@section('content')
    <a class="breadcrumb-link" href="{{ route('reviews.index') }}"><x-icon name="back" size="16" /> Reviewer Module</a>
    <div class="page-heading">
        <div><p class="eyebrow">Review workspace</p><h1>Review Assessment #{{ $assessment->id }}</h1><p>Assessor: {{ $assessment->user->name }} <span class="mx-2" aria-hidden="true">/</span> <x-status-badge :status="$review->status" /></p></div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="{{ route('assessments.report', $assessment) }}"><x-icon name="download" /> Download PDF</a>
        </div>
    </div>
    <dl class="metrics metrics-three">
        <div class="metric"><dt>Overall score</dt><dd>{{ $assessment->overall_score !== null ? number_format($assessment->overall_score * 100, 1).'%' : 'Not calculated' }}</dd></div>
        <div class="metric"><dt>Overall maturity</dt><dd>{{ $assessment->overall_maturity_level ?? 'Not calculated' }}</dd></div>
        <div class="metric"><dt>Assigned reviewer</dt><dd>{{ $review->reviewer?->name ?? 'Unassigned' }}</dd></div>
    </dl>
    <section class="card mb-4" aria-labelledby="actions-heading"><div class="card-header"><h2 class="h5 mb-0" id="actions-heading">Review actions</h2></div><div class="card-body"><x-review-actions :review="$review" /></div></section>
    <section class="card mb-4" aria-labelledby="maturity-radar-heading">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="h5 mb-2" id="maturity-radar-heading">Element maturity profile</h2>
                    <p class="text-secondary mb-0">Each element is scored from 0 (Initial) to 3 (Advanced).</p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('reviews.responses', $review) }}" aria-label="View assessment responses"><x-icon name="document" size="18" /><span class="visually-hidden">View assessment responses</span></a>
            </div>
            <div class="chart-container chart-container-radar mt-4" data-review-chart-container hidden>
                <div id="reviewMaturityRadarChart" role="img" aria-label="Radar chart of the 33 assessment element maturity scores, with element names shown around the chart."></div>
            </div>
        </div>
    </section>
    <section class="card" aria-labelledby="comments-heading"><div class="card-header"><h2 class="h5 mb-0" id="comments-heading">Comments</h2></div><div class="card-body"><x-review-comments :review="$review" /></div></section>
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        window.ncsbasReviewData = @js([
            'labels' => collect(range(1, 33))->map(fn (int $number) => $results->get($number)?->element_name ?? $elements->get($number)?->first()?->element_name ?? 'Element '.$number)->values(),
            'scores' => collect(range(1, 33))->map(fn (int $number) => $results->get($number)?->maturity_score ?? 0)->values(),
            'maturityLevels' => collect(range(1, 33))->map(fn (int $number) => $results->get($number)?->maturity_level ?? 'Initial')->values(),
            'yesCounts' => collect(range(1, 33))->map(fn (int $number) => $results->get($number)?->yes_count ?? 0)->values(),
        ]);
    </script>
@endsection
