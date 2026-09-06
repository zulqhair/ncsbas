<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentResponse;
use App\Models\NcsbQuestion;
use App\Services\NcsbScoringService;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            $assessments = Assessment::with('user')->latest()->get();
        } elseif ($user->role === 'reviewer') {
            $assessments = Assessment::whereHas(
                'reviews',
                fn ($query) => $query
                    ->where('reviewer_id', $user->id)
                    ->whereIn('status', ['pending', 'accepted', 'completed'])
            )
                ->with('user')->latest()->get();
        } else {
            $assessments = $user->assessments()->latest()->get();
        }

        return view('assessments.index', compact('assessments'));
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->isAdmin() || $request->user()->role === 'assessor', 403);

        $assessment = Assessment::create(['user_id' => $request->user()->id]);

        return redirect()->route('assessments.show', $assessment);
    }

    public function show(Request $request, Assessment $assessment)
    {
        $user = $request->user();
        $isOwner = $assessment->user_id === $user->id;
        $isAdmin = $user->isAdmin();
        $isAssignedReviewer = $assessment->reviews()
            ->where('reviewer_id', $user->id)
            ->whereIn('status', ['pending', 'accepted', 'completed'])
            ->exists();
        abort_unless($isOwner || $isAdmin || $isAssignedReviewer, 403);

        return view('assessments.show', [
            'assessment' => $assessment->load([
                'user',
                'reviews.reviewer',
                'reviews.comments.user',
            ]),
            'elements' => NcsbQuestion::orderBy('number')->get()->groupBy('element_number'),
            'answers' => $assessment->responses()->pluck('answer', 'ncsb_question_id'),
            'results' => $assessment->elementResults()
                ->orderBy('element_number')
                ->get()
                ->keyBy('element_number'),
            'canEdit' => ($isOwner || $isAdmin) && $assessment->status === 'draft',
            'canAssignReview' => $isAdmin || ($isOwner && $assessment->status === 'draft'),
        ]);
    }

    public function save(Request $request, Assessment $assessment)
    {
        abort_unless(
            $assessment->user_id === $request->user()->id || $request->user()->isAdmin(),
            403
        );
        abort_unless(
            $assessment->status === 'draft',
            422,
            'This assessment is locked after review is requested.'
        );
        foreach ($request->input('answers', []) as $id => $answer) {
            if (in_array($answer, ['Yes', 'No'], true)) {
                AssessmentResponse::updateOrCreate(
                    ['assessment_id' => $assessment->id, 'ncsb_question_id' => $id],
                    ['answer' => $answer]
                );
            }
        }
        app(NcsbScoringService::class)->calculate($assessment);

        return back()->with('status', 'Draft saved and results recalculated.');
    }
}
