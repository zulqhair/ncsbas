<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssessmentDetailController extends Controller
{
    public function store(Request $request, Assessment $assessment): RedirectResponse
    {
        $this->authorizeManagement($request, $assessment);

        $assessment->details()->create($this->validatedDetails($request));

        return back()->with('status', 'Assessment detail added. You can now begin the questionnaire.');
    }

    public function update(Request $request, Assessment $assessment, AssessmentDetail $detail): RedirectResponse
    {
        $this->authorizeManagement($request, $assessment);
        abort_unless($detail->assessment_id === $assessment->id, 404);

        $detail->update($this->validatedDetails($request));

        return back()->with('status', 'Assessment detail updated.');
    }

    public function destroy(Request $request, Assessment $assessment, AssessmentDetail $detail): RedirectResponse
    {
        $this->authorizeManagement($request, $assessment);
        abort_unless($detail->assessment_id === $assessment->id, 404);

        $detail->delete();

        return back()->with('status', 'Assessment detail deleted.');
    }

    /**
     * @return array{label: string, value: string}
     */
    private function validatedDetails(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'value' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function authorizeManagement(Request $request, Assessment $assessment): void
    {
        abort_unless(
            ($assessment->user_id === $request->user()->id || $request->user()->isAdmin())
                && $assessment->status === 'draft',
            403
        );
    }
}
