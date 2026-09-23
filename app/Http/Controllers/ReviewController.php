<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $reviews = Review::query()
            ->when(
                ! $user->isAdmin(),
                fn ($query) => $query
                    ->where('reviewer_id', $user->id)
                    ->whereIn('status', ['pending', 'accepted', 'completed'])
            )
            ->with(['assessment.user', 'reviewer'])
            ->latest()
            ->get();

        return view('reviews.index', compact('reviews'));
    }

    public function show(Request $request, Review $review): View
    {
        $user = $request->user();
        $assigned = $review->reviewer_id === $user->id
            && in_array($review->status, ['accepted', 'completed'], true);
        abort_unless($user->isAdmin() || $assigned, 403);

        $review->load([
            'assessment.user',
            'assessment.elementResults',
            'reviewer',
            'comments.user',
        ]);

        $elements = NcsbQuestion::orderBy('number')->get()->groupBy('element_number');
        $results = $review->assessment->elementResults->keyBy('element_number');
        $domainMaturity = $elements
            ->groupBy(fn ($questions) => $questions->first()->domain)
            ->map(function ($questions, string $domain) use ($results): array {
                $scores = $questions
                    ->map(fn ($elementQuestions): float => (float) ($results->get($elementQuestions->first()->element_number)?->maturity_score ?? 0));

                return [
                    'name' => $domain,
                    'score' => round($scores->avg() ?? 0, 2),
                    'elementCount' => $scores->count(),
                ];
            })
            ->values();
        $elementMaturity = collect(range(1, 33))
            ->map(function (int $number) use ($elements, $results): array {
                $result = $results->get($number);

                return [
                    'number' => $number,
                    'name' => $result?->element_name ?? $elements->get($number)?->first()?->element_name ?? 'Element '.$number,
                    'score' => $result?->maturity_score ?? 0,
                    'maturityLevel' => $result?->maturity_level ?? 'Initial',
                    'yesCount' => $result?->yes_count ?? 0,
                ];
            });
        $maturityDistribution = collect([
            ['name' => 'Initial', 'score' => 0],
            ['name' => 'Basic', 'score' => 1],
            ['name' => 'Intermediate', 'score' => 2],
            ['name' => 'Advanced', 'score' => 3],
        ])->map(fn (array $level): array => [
            'name' => $level['name'],
            'count' => $elementMaturity->where('score', $level['score'])->count(),
        ]);
        $lowestElements = $elementMaturity
            ->sort(fn (array $first, array $second): int => $first['score'] <=> $second['score'] ?: $first['number'] <=> $second['number'])
            ->take(5)
            ->values();

        return view('reviews.show', [
            'review' => $review,
            'assessment' => $review->assessment,
            'elements' => $elements,
            'results' => $results,
            'domainMaturity' => $domainMaturity,
            'elementMaturity' => $elementMaturity,
            'maturityDistribution' => $maturityDistribution,
            'lowestElements' => $lowestElements,
        ]);
    }

    public function responses(Request $request, Review $review): View
    {
        $user = $request->user();
        $assigned = $review->reviewer_id === $user->id
            && in_array($review->status, ['accepted', 'completed'], true);
        abort_unless($user->isAdmin() || $assigned, 403);

        $review->load([
            'assessment.user',
            'assessment.responses',
            'reviewer',
        ]);

        return view('reviews.responses', [
            'review' => $review,
            'assessment' => $review->assessment,
            'elements' => NcsbQuestion::orderBy('number')->get()->groupBy('element_number'),
            'answers' => $review->assessment->responses->pluck('answer', 'ncsb_question_id'),
        ]);
    }

    public function request(Request $request, Assessment $assessment)
    {
        $user = $request->user();
        abort_unless($assessment->user_id === $user->id || $user->isAdmin(), 403);

        $adminReassignment = $user->isAdmin() && $assessment->status === 'open';
        if ($assessment->status !== 'draft' && ! $adminReassignment) {
            return back()->withErrors([
                'assessment' => 'This assessment has already been submitted or is no longer editable.',
            ]);
        }

        if (! $adminReassignment && ! $this->assessmentIsComplete($assessment)) {
            return back()->withErrors([
                'assessment' => 'Complete every element in sequence before submitting the assessment.',
            ]);
        }

        $data = $request->validate(['reviewer_id' => 'required|exists:users,id']);
        $reviewer = User::findOrFail($data['reviewer_id']);
        abort_unless($reviewer->role === User::ROLE_REVIEWER || $reviewer->isAdmin(), 422);

        $review = $assessment->reviews()->whereIn('status', ['pending', 'accepted'])->first();
        if ($review) {
            $review->update([
                'reviewer_id' => $reviewer->id,
                'requested_by_user_id' => $user->id,
                'status' => 'pending',
                'decline_reason' => null,
            ]);
        } else {
            $review = Review::create([
                'assessment_id' => $assessment->id,
                'reviewer_id' => $reviewer->id,
                'requested_by_user_id' => $user->id,
                'status' => 'pending',
            ]);
        }
        $assessment->update(['status' => 'open']);

        return back()->with('status', 'Review assigned and waiting for reviewer acceptance.');
    }

    private function assessmentIsComplete(Assessment $assessment): bool
    {
        $answers = $assessment->responses()->pluck('answer', 'ncsb_question_id');

        return NcsbQuestion::query()
            ->orderBy('number')
            ->get()
            ->groupBy('element_number')
            ->every(function ($questions) use ($answers): bool {
                $skipping = false;

                foreach ($questions as $question) {
                    $answer = $answers[$question->id] ?? null;

                    if ($skipping) {
                        if ($answer !== null) {
                            return false;
                        }

                        continue;
                    }

                    if ($answer === null) {
                        return false;
                    }

                    if ($answer === 'No') {
                        $skipping = true;
                    }
                }

                return true;
            });
    }

    public function update(Request $request, Review $review)
    {
        $user = $request->user();
        $assigned = $review->reviewer_id === $user->id
            && in_array($review->status, ['pending', 'accepted'], true);
        abort_unless($user->isAdmin() || $assigned, 403);
        $data = $request->validate([
            'action' => 'required|in:accept,decline,complete',
            'decline_reason' => 'nullable|string|max:1000',
        ]);
        if ($data['action'] === 'accept') {
            abort_unless($review->status === 'pending', 422, 'Only pending reviews can be accepted.');
        }
        if ($data['action'] === 'complete') {
            abort_unless($review->status === 'accepted', 422, 'Only accepted reviews can be completed.');
        }
        if ($data['action'] === 'decline') {
            abort_unless(in_array($review->status, ['pending', 'accepted'], true), 422, 'This review can no longer be declined.');
        }
        $status = match ($data['action']) {
            'accept' => 'accepted', 'complete' => 'completed', default => 'open'
        };
        $review->update([
            'status' => $status,
            'decline_reason' => $data['decline_reason'] ?? null,
        ]);
        $review->assessment->update([
            'status' => $status === 'completed'
                ? 'completed'
                : ($status === 'accepted' ? 'in_review' : 'open'),
        ]);

        return back()->with('status', 'Review status updated.');
    }

    public function comment(Request $request, Review $review)
    {
        $user = $request->user();
        $assigned = $review->reviewer_id === $user->id
            && in_array($review->status, ['accepted', 'completed'], true);
        abort_unless(
            $user->isAdmin()
                || $assigned
                || $review->assessment->user_id === $user->id,
            403
        );
        $data = $request->validate(['body' => 'required|string|max:5000']);
        ReviewComment::create([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Comment added.');
    }
}
