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

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Recent assessments</h2>
                <a class="small" href="{{ route('assessments.index') }}">View all</a>
            </div>
            @forelse($assessments->take(5) as $assessment)
                <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom py-3">
                    <div>
                        <a class="fw-semibold text-decoration-none" href="{{ route('assessments.show', $assessment) }}">
                            Assessment #{{ $assessment->id }}
                        </a>
                        @if(auth()->user()->isAdmin())
                            <div class="small text-secondary">{{ $assessment->user->name }}</div>
                        @endif
                    </div>
                    <div class="text-end">
                        <span class="badge text-bg-secondary">{{ ucfirst(str_replace('_', ' ', $assessment->status)) }}</span>
                        <div class="small text-secondary mt-1">
                            {{ $assessment->overall_score !== null ? number_format($assessment->overall_score * 100, 1).'%' : 'Not scored' }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-secondary mb-0">No assessments are available yet.</p>
            @endforelse
        </div>
    </div>

    <script>
        window.ncsbasDashboardData = @js($chartData);
    </script>
@endsection
