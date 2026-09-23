<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use App\Models\RoleModulePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AssessmentWorkbookImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RoleModulePermission::query()->firstOrCreate([
            'role' => User::ROLE_ASSESSOR,
            'module' => RoleModulePermission::MODULE_ASSESSOR,
        ]);
    }

    public function test_assessor_can_replace_draft_answers_with_a_completed_workbook(): void
    {
        $questions = $this->createBaselineQuestions();
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);
        $assessment->details()->create(['label' => 'Organisation name', 'value' => 'NCSB Agency']);
        $assessment->responses()->create([
            'ncsb_question_id' => $questions[3]->id,
            'answer' => 'Yes',
        ]);

        $this->assertNotSame($questions[1]->number, $questions[1]->id);
        $this->actingAs($assessor)
            ->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Import completed workbook')
            ->assertSee(route('assessments.import', $assessment), false);

        $this->actingAs($assessor)
            ->post(route('assessments.import', $assessment), [
                'workbook' => $this->workbookUpload([1 => 'Yes', 2 => 'No']),
                'replace_answers' => '1',
            ])
            ->assertSessionHas('status', 'Workbook imported. 2 response(s) replaced and results recalculated.');

        $this->assertDatabaseHas('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[1]->id,
            'answer' => 'Yes',
        ]);
        $this->assertDatabaseHas('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[2]->id,
            'answer' => 'No',
        ]);
        $this->assertDatabaseMissing('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[3]->id,
        ]);
        $this->assertDatabaseHas('assessment_element_results', [
            'assessment_id' => $assessment->id,
            'element_number' => 1,
            'yes_count' => 1,
            'maturity_score' => 0,
        ]);
    }

    public function test_workbook_answers_after_a_no_are_rejected_without_replacing_the_draft(): void
    {
        $questions = $this->createBaselineQuestions();
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);
        $assessment->details()->create(['label' => 'Organisation name', 'value' => 'NCSB Agency']);
        $assessment->responses()->create([
            'ncsb_question_id' => $questions[3]->id,
            'answer' => 'Yes',
        ]);

        $this->actingAs($assessor)
            ->post(route('assessments.import', $assessment), [
                'workbook' => $this->workbookUpload([1 => 'No', 2 => 'Yes']),
                'replace_answers' => '1',
            ])
            ->assertSessionHasErrors('workbook');

        $this->assertDatabaseHas('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[3]->id,
            'answer' => 'Yes',
        ]);
    }

    public function test_workbook_without_the_questionnaire_sheet_is_rejected_without_replacing_the_draft(): void
    {
        $questions = $this->createBaselineQuestions();
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);
        $assessment->details()->create(['label' => 'Organisation name', 'value' => 'NCSB Agency']);
        $assessment->responses()->create([
            'ncsb_question_id' => $questions[1]->id,
            'answer' => 'Yes',
        ]);

        $this->actingAs($assessor)
            ->post(route('assessments.import', $assessment), [
                'workbook' => $this->workbookUpload([], 'Other worksheet'),
                'replace_answers' => '1',
            ])
            ->assertSessionHasErrors('workbook');

        $this->assertDatabaseHas('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[1]->id,
            'answer' => 'Yes',
        ]);
    }

    public function test_assessor_cannot_import_a_workbook_into_another_assessors_draft(): void
    {
        $this->createBaselineQuestions();
        $owner = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $otherAssessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $owner->id]);
        $assessment->details()->create(['label' => 'Organisation name', 'value' => 'NCSB Agency']);

        $this->actingAs($otherAssessor)
            ->post(route('assessments.import', $assessment), [
                'workbook' => $this->workbookUpload([1 => 'Yes']),
                'replace_answers' => '1',
            ])
            ->assertForbidden();
    }

    /**
     * @return Collection<int, NcsbQuestion>
     */
    private function createBaselineQuestions(): Collection
    {
        return collect(range(1, 125))
            ->reverse()
            ->map(function (int $number): NcsbQuestion {
                $elementNumber = $number <= 104
                    ? intdiv($number - 1, 4) + 1
                    : intdiv($number - 105, 3) + 27;

                return NcsbQuestion::create([
                    'number' => $number,
                    'domain' => 'Test domain',
                    'category' => 'Test category',
                    'element_number' => $elementNumber,
                    'element_name' => 'Element '.$elementNumber,
                    'question' => 'Question '.$number,
                ]);
            })
            ->keyBy('number');
    }

    /**
     * @param  array<int, string>  $answers
     */
    private function workbookUpload(array $answers, string $sheetName = 'CS Baseline Questionnaires'): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setTitle($sheetName);

        foreach (range(1, 125) as $questionNumber) {
            $worksheet->setCellValue('B'.($questionNumber + 2), $questionNumber);
            $worksheet->setCellValue('H'.($questionNumber + 2), $answers[$questionNumber] ?? null);
        }

        $path = tempnam(sys_get_temp_dir(), 'ncsb-workbook-');
        (new Xlsx($spreadsheet))->save($path);
        $contents = file_get_contents($path);
        unlink($path);

        return UploadedFile::fake()->createWithContent('ncsb-v1.1.xlsx', $contents);
    }
}
