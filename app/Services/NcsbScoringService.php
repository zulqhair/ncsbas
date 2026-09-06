<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentElementResult;
use App\Models\NcsbQuestion;

class NcsbScoringService
{
    public function calculate(Assessment $assessment): void
    {
        $questions = NcsbQuestion::query()->orderBy('number')->get()->groupBy('element_number');
        $answers = $assessment->responses()->pluck('answer', 'ncsb_question_id');
        $answeredElements = 0;

        foreach ($questions as $elementNumber => $items) {
            $yes = $items->filter(fn ($question) => ($answers[$question->id] ?? null) === 'Yes')->count();
            if ($yes > 0) { $answeredElements++; }
            $score = $this->elementScore((int) $elementNumber, $yes, $items->count());
            AssessmentElementResult::updateOrCreate(
                ['assessment_id' => $assessment->id, 'element_number' => $elementNumber],
                ['element_name' => $items->first()->element_name, 'yes_count' => $yes, 'maturity_score' => $score, 'maturity_level' => ['Initial', 'Basic', 'Intermediate', 'Advanced'][$score]],
            );
        }

        $overall = $answeredElements / 33;
        $assessment->update(['overall_score' => $overall, 'overall_maturity_level' => $overall <= .24 ? 'Initial' : ($overall <= .49 ? 'Basic' : ($overall <= .74 ? 'Intermediate' : 'Advanced'))]);
    }

    private function elementScore(int $number, int $yes, int $count): int
    {
        if ($count === 3) { return $yes; }
        if ($number === 21) { return $yes <= 2 ? 0 : ($yes === 3 ? 1 : ($yes === 4 ? 2 : 3)); }
        if ($number === 29) { return $yes === 0 ? 0 : ($yes <= 2 ? 1 : ($yes <= 4 ? 2 : 3)); }
        if (in_array($number, [10, 23], true)) { return $yes === 0 ? 0 : ($yes === 1 ? 1 : ($yes <= 3 ? 2 : 3)); }
        return $yes <= 1 ? 0 : ($yes === 2 ? 1 : ($yes < $count ? 2 : 3));
    }
}
