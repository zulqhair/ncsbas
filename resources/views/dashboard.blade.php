@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-primary text-uppercase small fw-semibold mb-1">Dashboard</p>
            <h1 class="h2 mb-1">{{ $roleTitle }}</h1>
            <p class="text-secondary mb-0">Assessment activity and NCSB maturity at a glance.</p>
        </div>
        @if(auth()->user()->isAdmin() || auth()->user()->role === 'assessor')
            <form method="POST" action="{{ route('assessments.create') }}">
                @csrf
                <button class="btn btn-primary">New assessment</button>
            </form>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card dashboard-stat h-100">
                <div class="card-body">
                    <div class="text-secondary small">Visible assessments</div>
                    <div class="display-6 fw-semibold">{{ $summary['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card dashboard-stat h-100">
                <div class="card-body">
                    <div class="text-secondary small">Active reviews</div>
                    <div class="display-6 fw-semibold">{{ $summary['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card dashboard-stat h-100">
                <div class="card-body">
                    <div class="text-secondary small">Completed</div>
                    <div class="display-6 fw-semibold">{{ $summary['completed'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card dashboard-stat h-100">
                <div class="card-body">
                    <div class="text-secondary small">Average score</div>
                    <div class="display-6 fw-semibold">
                        {{ $summary['average'] !== null ? number_format($summary['average'] * 100, 1).'%' : '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <div class="card dashboard-chart-card h-100">
                <div class="card-body">
                    <h2 class="h5">Assessment status</h2>
                    <p class="small text-secondary">Current workflow distribution.</p>
                    <div class="chart-container chart-container-sm">
                        <canvas id="assessmentStatusChart" aria-label="Assessment status chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-7">
            <div class="card dashboard-chart-card h-100">
                <div class="card-body">
                    <h2 class="h5">Maturity distribution</h2>
                    <p class="small text-secondary">Overall NCSB maturity levels.</p>
                    <div class="chart-container chart-container-sm">
                        <canvas id="maturityChart" aria-label="Maturity distribution chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card dashboard-chart-card mb-4">
        <div class="card-body">
            <h2 class="h5">Assessment scores</h2>
            <p class="small text-secondary">Overall score for each visible assessment.</p>
            <div class="chart-container chart-container-md">
                <canvas id="assessmentScoresChart" aria-label="Assessment score chart"></canvas>
            </div>
        </div>
    </div>

    <div class="card dashboard-chart-card mb-4">
        <div class="card-body">
            <h2 class="h5">Element performance</h2>
            <p class="small text-secondary">Average maturity score across the 33 NCSB elements.</p>
            <div class="chart-container chart-container-lg">
                <canvas id="elementPerformanceChart" aria-label="Element performance chart"></canvas>
            </div>
        </div>
    </div>

    <script>
        window.ncsbasDashboardData = @js($chartData);
    </script>
@endsection
