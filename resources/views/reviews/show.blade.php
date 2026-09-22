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
    <dl class="metrics metrics-two">
        <div class="metric"><dt>Overall score</dt><dd>{{ $assessment->overall_score !== null ? number_format($assessment->overall_score * 100, 1).'%' : 'Not calculated' }}</dd></div>
        <div class="metric"><dt>Overall maturity</dt><dd>{{ $assessment->overall_maturity_level ?? 'Not calculated' }}</dd></div>
    </dl>
    <div class="row g-4 mb-4">
        <div class="col-lg-7"><section class="card h-100" aria-labelledby="maturity-radar-heading"><div class="card-body">
            <div>
                    <h2 class="h5 mb-2" id="maturity-radar-heading">Domain maturity profile</h2>
                    <p class="text-secondary mb-0">Each domain score is the average maturity of its elements, from 0 (Initial) to 3 (Advanced).</p>
            </div>
            <div class="chart-container chart-container-radar mt-4" data-review-domain-chart-container hidden>
                <div id="reviewDomainRadarChart" role="img" aria-label="Radar chart of maturity scores for the six NCSB domains."></div>
            </div>
        </div></section></div>
        <div class="col-lg-5"><section class="card h-100" aria-labelledby="maturity-distribution-heading"><div class="card-body">
            <h2 class="h5 mb-2" id="maturity-distribution-heading">Maturity distribution</h2>
            <p class="text-secondary mb-0">How the 33 elements are distributed across the maturity levels.</p>
            <div class="chart-container chart-container-sm mt-4" data-review-distribution-chart-container hidden>
                <div id="reviewMaturityDistributionChart" role="img" aria-label="Donut chart showing the count of elements at each maturity level."></div>
            </div>
        </div></section></div>
    </div>
    <section class="card mb-4" aria-labelledby="lowest-elements-heading"><div class="card-body">
        <h2 class="h5 mb-2" id="lowest-elements-heading">Elements needing attention</h2>
        <p class="text-secondary mb-0">The five lowest-scoring elements, ordered from lowest to highest maturity.</p>
        <div class="chart-container chart-container-sm mt-4" data-review-lowest-chart-container hidden>
            <div id="reviewLowestElementsChart" role="img" aria-label="Horizontal bar chart of the five elements needing the most attention."></div>
        </div>
    </div></section>
    <section class="card mb-4" aria-labelledby="element-maturity-heading">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="h5 mb-2" id="element-maturity-heading">Element maturity scores</h2>
                    <p class="text-secondary mb-0">The 33 elements follow the original NCSB template order. Scores range from 0 (Initial) to 3 (Advanced).</p>
                </div>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('reviews.responses', $review) }}" aria-label="View assessment responses"><x-icon name="document" size="18" /><span class="visually-hidden">View assessment responses</span></a>
            </div>
            <div class="chart-container chart-container-lg mt-4" data-review-element-chart-container hidden>
                <div id="reviewElementMaturityChart" role="img" aria-label="Horizontal bar chart of the 33 assessment element maturity scores."></div>
            </div>
        </div>
    </section>
    <section class="card" aria-labelledby="comments-heading"><div class="card-header"><h2 class="h5 mb-0" id="comments-heading">Comments</h2></div><div class="card-body"><x-review-comments :review="$review" /></div></section>
    <section class="card mt-4" aria-labelledby="actions-heading"><div class="card-header"><h2 class="h5 mb-0" id="actions-heading">Review actions</h2></div><div class="card-body"><x-review-actions :review="$review" /></div></section>
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">
        window.ncsbasReviewData = @js([
            'domains' => $domainMaturity,
            'elements' => $elementMaturity,
            'maturityDistribution' => $maturityDistribution,
            'lowestElements' => $lowestElements,
        ]);
    </script>
@endsection
