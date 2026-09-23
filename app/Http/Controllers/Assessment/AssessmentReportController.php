<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\NcsbQuestion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AssessmentReportController extends Controller
{
    public function download(Request $request, Assessment $assessment)
    {
        $user = $request->user();
        $assigned = $assessment->reviews()
            ->where('reviewer_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->exists();
        abort_unless($assessment->user_id === $user->id || $user->isAdmin() || $assigned, 403);
        $assessment->load('details', 'elementResults', 'user');
        $elements = NcsbQuestion::orderBy('number')->get()->groupBy('element_number');
        $results = $assessment->elementResults->keyBy('element_number');

        return Pdf::loadView('assessments.report', compact('assessment', 'elements', 'results'))
            ->download('ncsbas-assessment-'.$assessment->id.'.pdf');
    }
}
