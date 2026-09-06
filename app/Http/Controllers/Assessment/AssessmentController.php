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
    public function index(Request $request) { return view('assessments.index', ['assessments' => $request->user()->assessments()->latest()->get()]); }
    public function create(Request $request) { return redirect()->route('assessments.show', Assessment::create(['user_id' => $request->user()->id])); }
    public function show(Request $request, Assessment $assessment) { abort_unless($assessment->user_id === $request->user()->id || $request->user()->isAdmin(), 403); return view('assessments.show', ['assessment' => $assessment, 'elements' => NcsbQuestion::orderBy('number')->get()->groupBy('element_number'), 'answers' => $assessment->responses()->pluck('answer', 'ncsb_question_id'), 'results' => $assessment->elementResults()->get()->keyBy('element_number')]); }
    public function save(Request $request, Assessment $assessment) { abort_unless($assessment->user_id === $request->user()->id || $request->user()->isAdmin(), 403); foreach ($request->input('answers', []) as $id => $answer) { if (in_array($answer, ['Yes', 'No'], true)) { AssessmentResponse::updateOrCreate(['assessment_id' => $assessment->id, 'ncsb_question_id' => $id], ['answer' => $answer]); } } app(NcsbScoringService::class)->calculate($assessment); return back()->with('status', 'Draft saved and results recalculated.'); }
}
