<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\NcsbQuestion;
use App\Models\User;
use Tests\TestCase;

class AssessmentDetailTest extends TestCase
{
    public function test_assessor_can_manage_details_in_the_assessment_modal(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);

        $this->actingAs($assessor)
            ->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Assessment details')
            ->assertSee('data-assessment-details-modal', false)
            ->assertSee('data-open-on-load', false)
            ->assertSee('Set up this assessment first')
            ->assertDontSee('data-sequential-questionnaire', false);

        $this->post(route('assessments.details.store', $assessment), [
            'label' => 'Organisation name',
            'value' => 'NCSB Agency',
        ])->assertSessionHas('status');

        $detail = AssessmentDetail::firstOrFail();
        $this->assertDatabaseHas('assessment_details', [
            'assessment_id' => $assessment->id,
            'label' => 'Organisation name',
            'value' => 'NCSB Agency',
        ]);

        $this->put(route('assessments.details.update', [$assessment, $detail]), [
            'label' => 'Assessment scope',
            'value' => 'Corporate systems',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('assessment_details', [
            'id' => $detail->id,
            'label' => 'Assessment scope',
            'value' => 'Corporate systems',
        ]);

        $this->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Assessment scope')
            ->assertSee('Corporate systems')
            ->assertSee('data-edit-assessment-detail', false)
            ->assertSee('data-sequential-questionnaire', false);

        $this->delete(route('assessments.details.destroy', [$assessment, $detail]))
            ->assertSessionHas('status');
        $this->assertDatabaseMissing('assessment_details', ['id' => $detail->id]);
    }

    public function test_details_are_required_before_answers_can_be_saved_and_cannot_be_changed_when_locked(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);
        $question = NcsbQuestion::create([
            'number' => 1,
            'domain' => 'Govern',
            'category' => 'Policy',
            'element_number' => 1,
            'element_name' => 'Policy management',
            'question' => 'Is a policy in place?',
        ]);

        $this->actingAs($assessor)
            ->put(route('assessments.save', $assessment), ['answers' => [$question->id => 'Yes']])
            ->assertSessionHasErrors('assessment');

        $detail = $assessment->details()->create([
            'label' => 'Organisation name',
            'value' => 'NCSB Agency',
        ]);
        $assessment->update(['status' => 'open']);

        $this->put(route('assessments.details.update', [$assessment, $detail]), [
            'label' => 'Assessment scope',
            'value' => 'Corporate systems',
        ])->assertForbidden();
    }

    public function test_assessor_cannot_manage_another_assessment_details(): void
    {
        $owner = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $otherAssessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $owner->id]);

        $this->actingAs($otherAssessor)
            ->post(route('assessments.details.store', $assessment), [
                'label' => 'Organisation name',
                'value' => 'NCSB Agency',
            ])
            ->assertForbidden();
    }
}
