<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $assessments = $this->visibleAssessments($user);
        $questions = NcsbQuestion::query()
            ->select(['element_number', 'element_name'])
            ->orderBy('element_number')
            ->get()
            ->groupBy('element_number');

        $statusLabels = [
            'draft' => 'Draft',
            'open' => 'Open',
            'in_review' => 'In review',
            'completed' => 'Completed',
        ];
        $maturityLabels = ['Initial', 'Basic', 'Intermediate', 'Advanced'];
        $statusCounts = collect($statusLabels)
            ->mapWithKeys(fn (string $label, string $status) => [$label => $assessments->where('status', $status)->count()]);
        $maturityCounts = collect($maturityLabels)
            ->mapWithKeys(fn (string $label) => [$label => $assessments->where('overall_maturity_level', $label)->count()]);

        $elementScores = collect(range(1, 33))->map(function (int $number) use ($assessments, $questions): array {
            $results = $assessments
                ->flatMap(fn (Assessment $assessment) => $assessment->elementResults->where('element_number', $number));
            $average = $results->isEmpty() ? 0 : round($results->avg('maturity_score') / 3 * 100, 1);

            return [
                'label' => $questions->get($number)?->first()?->element_name ?? 'Element '.$number,
                'score' => $average,
            ];
        })->values();

        $chartData = [
            'assessments' => $assessments->map(fn (Assessment $assessment): array => [
                'label' => 'Assessment #'.$assessment->id,
                'score' => $assessment->overall_score !== null
                    ? round($assessment->overall_score * 100, 1) : null,
                'status' => $statusLabels[$assessment->status] ?? ucfirst($assessment->status),
            ])->values(),
            'statuses' => [
                'labels' => $statusCounts->keys()->values(),
                'values' => $statusCounts->values(),
            ],
            'maturity' => [
                'labels' => $maturityCounts->keys()->values(),
                'values' => $maturityCounts->values(),
            ],
            'elements' => [
                'labels' => $elementScores->pluck('label')->values(),
                'values' => $elementScores->pluck('score')->values(),
            ],
        ];

        return view('dashboard', [
            'assessments' => $assessments,
            'chartData' => $chartData,
            'roleTitle' => $user->isAdmin()
                ? 'Administrator overview'
                : ($user->role === User::ROLE_REVIEWER
                    ? 'Assigned review overview'
                    : 'My assessment overview'),
            'summary' => [
                'total' => $assessments->count(),
                'completed' => $assessments->where('status', 'completed')->count(),
                'active' => $assessments->whereIn('status', ['open', 'in_review'])->count(),
                'average' => $assessments->whereNotNull('overall_score')->avg('overall_score'),
            ],
        ]);
    }

    private function visibleAssessments(User $user)
    {
        return Assessment::query()
            ->when(
                $user->isAdmin(),
                fn ($query) => $query,
                fn ($query) => $user->role === User::ROLE_REVIEWER
                    ? $query->whereHas('reviews', fn ($review) => $review
                        ->where('reviewer_id', $user->id)
                        ->whereIn('status', ['pending', 'accepted', 'completed']))
                    : $query->where('user_id', $user->id)
            )
            ->with(['user', 'elementResults'])
            ->latest()
            ->get();
    }
}
