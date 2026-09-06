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
        $questions = NcsbQuestion::query()->orderBy('number')->get();
        $submittedAnswers = $request->input('answers', []);
        $submittedAnswers = is_array($submittedAnswers) ? $submittedAnswers : [];
        $questionIds = $questions->mapWithKeys(
            fn (NcsbQuestion $question): array => [(string) $question->id => $question]
        );
        $answers = [];
        $errors = [];

        foreach ($submittedAnswers as $id => $answer) {
            $question = $questionIds->get((string) $id);
            if (! $question) {
                $errors[] = 'One or more submitted questions are invalid.';

                continue;
            }
            if (! in_array($answer, ['Yes', 'No'], true)) {
                $errors[] = 'Each response must be Yes or No.';

                continue;
            }

            $answers[(string) $question->id] = $answer;
        }

        foreach ($questions->groupBy('element_number') as $elementQuestions) {
            $blockedReason = null;

            foreach ($elementQuestions as $question) {
                $answer = $answers[(string) $question->id] ?? null;

                if ($blockedReason !== null) {
                    if ($answer !== null) {
                        $errors[] = $blockedReason === 'no'
                            ? "Question {$question->number} must be skipped because an earlier question in this element was answered No."
                            : "Question {$question->number} cannot be answered before the previous question in this element. Answer each question in sequence.";
                    }

                    continue;
                }

                if ($answer === null) {
                    $blockedReason = 'incomplete';

                    continue;
                }

                if ($answer === 'No') {
                    $blockedReason = 'no';
                }
            }
        }

        if ($errors !== []) {
            return back()->withInput()->withErrors($errors);
        }

        $assessment->responses()->delete();
        foreach ($answers as $questionId => $answer) {
            AssessmentResponse::create([
                'assessment_id' => $assessment->id,
                'ncsb_question_id' => $questionId,
                'answer' => $answer,
            ]);
        }
        app(NcsbScoringService::class)->calculate($assessment);

        return back()->with('status', 'Draft saved and results recalculated.');
    }
}
