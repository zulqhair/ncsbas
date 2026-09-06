<?php
namespace App\Http\Controllers\Assessment;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentResponse;
use App\Models\NcsbQuestion;
use Illuminate\Http\Request;
class AssessmentController extends Controller { public function index(Request $r) { return view('assessments.index',['assessments'=>$r->user()->assessments()->latest()->get()]); } public function create(Request $r) { return redirect()->route('assessments.show', Assessment::create(['user_id'=>$r->user()->id])); } public function show(Request $r, Assessment $assessment) { abort_unless($assessment->user_id===$r->user()->id || $r->user()->isAdmin(),403); return view('assessments.show',['assessment'=>$assessment,'elements'=>NcsbQuestion::orderBy('number')->get()->groupBy('element_number'),'answers'=>$assessment->responses()->pluck('answer','ncsb_question_id')]); } public function save(Request $r, Assessment $assessment) { abort_unless($assessment->user_id===$r->user()->id || $r->user()->isAdmin(),403); foreach($r->input('answers',[]) as $id=>$answer) if(in_array($answer,['Yes','No'],true)) AssessmentResponse::updateOrCreate(['assessment_id'=>$assessment->id,'ncsb_question_id'=>$id],['answer'=>$answer]); return back()->with('status','Draft saved.'); } }
