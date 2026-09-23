@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
    <section class="hero-panel dashboard-intro" aria-labelledby="dashboard-heading">
        <div><p class="eyebrow">Dashboard · NCSB v1.1</p><h1 id="dashboard-heading">{{ $roleTitle }}</h1><p>Assessment activity and NCSB maturity at a glance.</p></div>
        <div class="hero-actions">
            @if(auth()->user()->canAccessModule('assessor') && (auth()->user()->isAdmin() || auth()->user()->role === 'assessor'))
                <form method="POST" action="{{ route('assessments.create') }}" data-loading-form>@csrf<button class="btn btn-light" type="submit" data-loading-label="Creating draft…"><x-icon name="plus" /> New assessment</button></form>
            @elseif(auth()->user()->canAccessModule('reviewer'))
                <a class="btn btn-light" href="{{ route('reviews.index') }}">View assignments <x-icon name="arrow" /></a>
            @endif
        </div>
    </section>
    <dl class="metrics">
        <div class="metric"><dt>Visible assessments</dt><dd>{{ $summary['total'] }}<span class="metric-caption">Within your access</span></dd></div>
        <div class="metric"><dt>Active reviews</dt><dd>{{ $summary['active'] }}<span class="metric-caption">Open or in review</span></dd></div>
        <div class="metric"><dt>Completed</dt><dd>{{ $summary['completed'] }}<span class="metric-caption">Review completed</span></dd></div>
        <div class="metric"><dt>Average score</dt><dd>{{ $summary['average'] !== null ? number_format($summary['average'] * 100, 1).'%' : '—' }}<span class="metric-caption">Across scored assessments</span></dd></div>
    </dl>
    @if($assessments->isEmpty())
        <div class="card empty-state"><x-icon name="document" size="36" /><h2>No assessments to display yet</h2><p>@if(! auth()->user()->canAccessModule('assessor') && ! auth()->user()->canAccessModule('reviewer')) Your administrator has not granted access to any modules. @elseif(auth()->user()->role === 'reviewer') Your dashboard will update when an assessment is assigned to you. @else Create an assessment to start building your cyber security baseline. @endif</p></div>
    @else
        <div class="section-heading"><div><p class="eyebrow">Assessment intelligence</p><h2 class="mb-0">Your baseline at a glance</h2></div></div>
        <div class="row g-4 mb-4">
            <div class="col-lg-5"><section class="card h-100" aria-labelledby="status-heading"><div class="card-body">
                <h3 id="status-heading">Assessment status</h3><p class="text-secondary">Current workflow distribution.</p>
                <div class="chart-container chart-container-sm" data-chart-container hidden><div id="assessmentStatusChart" role="img" aria-label="Assessment status chart. Values are in the table below."></div></div>
                <details class="chart-data" open><summary>View status data</summary><div class="table-responsive" role="region" aria-label="Assessment status data" tabindex="0"><table class="table"><thead><tr><th scope="col">Status</th><th scope="col">Assessments</th></tr></thead><tbody>@foreach($chartData['statuses']['labels'] as $index => $label)<tr><th scope="row">{{ $label }}</th><td>{{ $chartData['statuses']['values'][$index] }}</td></tr>@endforeach</tbody></table></div></details>
            </div></section></div>
            <div class="col-lg-7"><section class="card h-100" aria-labelledby="maturity-heading"><div class="card-body">
                <h3 id="maturity-heading">Maturity distribution</h3><p class="text-secondary">Overall NCSB maturity levels.</p>
                <div class="chart-container chart-container-sm" data-chart-container hidden><div id="maturityChart" role="img" aria-label="Maturity distribution chart. Values are in the table below."></div></div>
                <details class="chart-data" open><summary>View maturity data</summary><div class="table-responsive" role="region" aria-label="Maturity distribution data" tabindex="0"><table class="table"><thead><tr><th scope="col">Maturity</th><th scope="col">Assessments</th></tr></thead><tbody>@foreach($chartData['maturity']['labels'] as $index => $label)<tr><th scope="row">{{ $label }}</th><td>{{ $chartData['maturity']['values'][$index] }}</td></tr>@endforeach</tbody></table></div></details>
            </div></section></div>
        </div>
        <section class="card mb-4" aria-labelledby="scores-heading"><div class="card-body">
            <h3 id="scores-heading">Assessment scores</h3><p class="text-secondary">Overall score for each visible assessment, newest first.</p>
            <div class="chart-container" data-chart-container hidden><div id="assessmentScoresChart" role="img" aria-label="Assessment score chart. Values are in the table below."></div></div>
            <details class="chart-data" open><summary>View assessment score data</summary><div class="table-responsive" role="region" aria-label="Assessment scores" tabindex="0"><table class="table"><thead><tr><th scope="col">Assessment</th><th scope="col">Score</th><th scope="col">Status</th></tr></thead><tbody>@foreach($chartData['assessments'] as $item)<tr><th scope="row">{{ $item['label'] }}</th><td>{{ $item['score'] !== null ? $item['score'].'%' : 'Not calculated' }}</td><td>{{ $item['status'] }}</td></tr>@endforeach</tbody></table></div></details>
        </div></section>
        <section class="card" aria-labelledby="elements-heading"><div class="card-body">
            <h3 id="elements-heading">Element performance</h3><p class="text-secondary">Average maturity score across the 33 NCSB elements. Each element is scored from 0 to 3 and shown as a percentage.</p>
            <div class="chart-container chart-container-lg" data-chart-container hidden><div id="elementPerformanceChart" role="img" aria-label="Element performance chart. Full element names and values are in the table below."></div></div>
            <details class="chart-data" open><summary>View all element scores</summary><div class="table-responsive" role="region" aria-label="Element performance data" tabindex="0"><table class="table"><thead><tr><th scope="col">Element</th><th scope="col">Average maturity score</th></tr></thead><tbody>@foreach($chartData['elements']['labels'] as $index => $label)<tr><th scope="row">{{ $index + 1 }}. {{ $label }}</th><td>{{ $chartData['elements']['values'][$index] }}%</td></tr>@endforeach</tbody></table></div></details>
        </div></section>
    @endif
    <script nonce="{{ request()->attributes->get('csp_nonce') }}">window.ncsbasDashboardData = @js($chartData);</script>
@endsection
