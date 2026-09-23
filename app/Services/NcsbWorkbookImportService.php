<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class NcsbWorkbookImportService
{
    private const int QUESTION_COUNT = 125;

    private const string QUESTIONNAIRE_SHEET = 'CS Baseline Questionnaires';

    public function __construct(private NcsbScoringService $scoringService) {}

    public function import(Assessment $assessment, UploadedFile $workbookFile): int
    {
        $worksheet = $this->loadQuestionnaireWorksheet($workbookFile);
        $questions = $this->baselineQuestions();
        $answers = $this->extractAnswers($worksheet, $questions);

        DB::transaction(function () use ($assessment, $answers): void {
            $assessment->responses()->delete();
            $assessment->responses()->createMany(
                collect($answers)
                    ->map(fn (string $answer, int $questionId): array => [
                        'ncsb_question_id' => $questionId,
                        'answer' => $answer,
                    ])
                    ->values()
                    ->all(),
            );
            $this->scoringService->calculate($assessment);
        });

        return count($answers);
    }

    private function loadQuestionnaireWorksheet(UploadedFile $workbookFile): Worksheet
    {
        $path = $workbookFile->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'workbook' => 'The uploaded workbook could not be read.',
            ]);
        }

        try {
            $worksheet = IOFactory::load($path)->getSheetByName(self::QUESTIONNAIRE_SHEET);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'workbook' => 'The uploaded file is not a readable NCSB v1.1 workbook.',
            ]);
        }

        if (! $worksheet instanceof Worksheet) {
            throw ValidationException::withMessages([
                'workbook' => 'The workbook is missing the CS Baseline Questionnaires sheet.',
            ]);
        }

        return $worksheet;
    }

    /**
     * @return Collection<int, NcsbQuestion>
     */
    private function baselineQuestions(): Collection
    {
        $questions = NcsbQuestion::query()
            ->orderBy('number')
            ->get();

        if (
            $questions->count() !== self::QUESTION_COUNT
            || $questions->pluck('number')->all() !== range(1, self::QUESTION_COUNT)
        ) {
            throw ValidationException::withMessages([
                'workbook' => 'The NCSB baseline questions are not available for workbook import.',
            ]);
        }

        return $questions->keyBy('number');
    }

    /**
     * @param  Collection<int, NcsbQuestion>  $questions
     * @return array<int, string>
     */
    private function extractAnswers(Worksheet $worksheet, Collection $questions): array
    {
        $answers = [];
        $errors = [];

        foreach (range(1, self::QUESTION_COUNT) as $questionNumber) {
            $row = $questionNumber + 2;
            $numberCell = $worksheet->getCell("B{$row}");
            $answerCell = $worksheet->getCell("H{$row}");
            $workbookQuestionNumber = $numberCell->getValue();

            if (
                $numberCell->getDataType() === DataType::TYPE_FORMULA
                || ! is_numeric($workbookQuestionNumber)
                || (int) $workbookQuestionNumber !== $questionNumber
            ) {
                $errors[] = "The workbook does not match the NCSB v1.1 question layout at row {$row}.";

                continue;
            }

            if ($answerCell->getDataType() === DataType::TYPE_FORMULA) {
                $errors[] = "Question {$questionNumber} must contain a typed Yes or No response, not a formula.";

                continue;
            }

            $answer = trim((string) ($answerCell->getValue() ?? ''));

            if ($answer === '') {
                continue;
            }

            $normalizedAnswer = match (Str::lower($answer)) {
                'yes' => 'Yes',
                'no' => 'No',
                default => null,
            };

            if ($normalizedAnswer === null) {
                $errors[] = "Question {$questionNumber} has an invalid response. Use Yes, No, or leave it blank.";

                continue;
            }

            $answers[$questions->get($questionNumber)->id] = $normalizedAnswer;
        }

        $this->validateResponseSequence($questions, $answers, $errors);

        if ($errors !== []) {
            throw ValidationException::withMessages(['workbook' => $errors]);
        }

        return $answers;
    }

    /**
     * @param  Collection<int, NcsbQuestion>  $questions
     * @param  array<int, string>  $answers
     * @param  list<string>  $errors
     */
    private function validateResponseSequence(Collection $questions, array $answers, array &$errors): void
    {
        foreach ($questions->groupBy('element_number') as $elementQuestions) {
            $remainingQuestionsMustBeBlank = false;

            foreach ($elementQuestions as $question) {
                $answer = $answers[$question->id] ?? null;

                if ($remainingQuestionsMustBeBlank && $answer !== null) {
                    $errors[] = "Question {$question->number} must be blank because an earlier question in this element is blank or No.";

                    continue;
                }

                if ($answer === null || $answer === 'No') {
                    $remainingQuestionsMustBeBlank = true;
                }
            }
        }
    }
}
