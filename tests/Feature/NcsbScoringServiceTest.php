<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use App\Models\User;
use App\Services\NcsbScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class NcsbScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scores for each possible number of Yes responses, transcribed from the
     * CS Baseline Scoring Dashboard formulas in the official workbook.
     *
     * @var array<int, list<int>>
     */
    private const WORKSHEET_SCORE_MATRIX = [
        1 => [0, 0, 1, 2, 2, 3],
        2 => [0, 1, 2, 3],
        3 => [0, 0, 1, 2, 3],
        4 => [0, 1, 2, 3],
        5 => [0, 1, 2, 3],
        6 => [0, 1, 2, 3],
        7 => [0, 0, 1, 2, 2, 3],
        8 => [0, 1, 2, 3],
        9 => [0, 0, 1, 2, 3],
        10 => [0, 1, 2, 2, 3],
        11 => [0, 0, 1, 2, 2, 3],
        12 => [0, 0, 1, 2, 3],
        13 => [0, 0, 1, 2, 3],
        14 => [0, 1, 2, 3],
        15 => [0, 0, 1, 2, 2, 3],
        16 => [0, 0, 1, 2, 3],
        17 => [0, 1, 2, 3],
        18 => [0, 1, 2, 3],
        19 => [0, 0, 1, 2, 2, 3],
        20 => [0, 1, 2, 3],
        21 => [0, 0, 0, 1, 2, 3],
        22 => [0, 0, 1, 2, 3],
        23 => [0, 1, 2, 2, 3],
        24 => [0, 1, 2, 3],
        25 => [0, 0, 1, 2, 3],
        26 => [0, 1, 2, 3],
        27 => [0, 0, 1, 2, 3],
        28 => [0, 0, 1, 2, 3],
        29 => [0, 1, 1, 2, 2, 3],
        30 => [0, 1, 2, 3],
        31 => [0, 1, 2, 3],
        32 => [0, 1, 2, 3],
        33 => [0, 0, 1, 2, 3],
    ];

    public function test_each_element_score_matches_the_verified_worksheet_formula(): void
    {
        $questionsByElement = $this->createQuestions();
        $user = User::factory()->create();
        $scoringService = app(NcsbScoringService::class);

        foreach (self::WORKSHEET_SCORE_MATRIX as $elementNumber => $expectedScores) {
            foreach ($expectedScores as $yesCount => $expectedScore) {
                $assessment = Assessment::create(['user_id' => $user->id]);

                $questionsByElement[$elementNumber]
                    ->take($yesCount)
                    ->each(fn (NcsbQuestion $question) => $assessment->responses()->create([
                        'ncsb_question_id' => $question->id,
                        'answer' => 'Yes',
                    ]));

                $scoringService->calculate($assessment);

                $actualScore = $assessment->elementResults()
                    ->where('element_number', $elementNumber)
                    ->value('maturity_score');

                $this->assertSame(
                    $expectedScore,
                    (int) $actualScore,
                    "Element {$elementNumber} with {$yesCount} Yes response(s) should score {$expectedScore}.",
                );
            }
        }
    }

    public function test_overall_maturity_levels_match_the_corrected_continuous_worksheet_bands(): void
    {
        $questionsByElement = $this->createQuestions();
        $user = User::factory()->create();
        $scoringService = app(NcsbScoringService::class);

        foreach ([0 => 'Initial', 7 => 'Initial', 8 => 'Basic', 16 => 'Basic', 17 => 'Intermediate', 24 => 'Intermediate', 25 => 'Advanced', 33 => 'Advanced'] as $answeredElements => $expectedLevel) {
            $assessment = Assessment::create(['user_id' => $user->id]);

            $questionsByElement
                ->take($answeredElements)
                ->each(fn (Collection $questions) => $assessment->responses()->create([
                    'ncsb_question_id' => $questions->first()->id,
                    'answer' => 'Yes',
                ]));

            $scoringService->calculate($assessment);
            $assessment->refresh();

            $this->assertSame($expectedLevel, $assessment->overall_maturity_level);
            $this->assertEqualsWithDelta(
                round($answeredElements / 33, 4),
                $assessment->overall_score,
                0.00001,
            );
        }
    }

    /**
     * @return Collection<int, Collection<int, NcsbQuestion>>
     */
    private function createQuestions(): Collection
    {
        $number = 1;

        return collect(self::WORKSHEET_SCORE_MATRIX)
            ->map(function (array $scores, int $elementNumber) use (&$number): Collection {
                return collect(range(1, count($scores) - 1))
                    ->map(function () use (&$number, $elementNumber): NcsbQuestion {
                        return NcsbQuestion::create([
                            'number' => $number++,
                            'domain' => 'Test domain',
                            'category' => 'Test category',
                            'element_number' => $elementNumber,
                            'element_name' => 'Element '.$elementNumber,
                            'question' => 'Question '.$number,
                        ]);
                    });
            });
    }
}
