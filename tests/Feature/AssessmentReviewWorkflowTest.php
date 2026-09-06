<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_answers_are_scored_and_results_are_visible(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        foreach (range(1, 3) as $number) {
            NcsbQuestion::create([
                'number' => $number,
                'domain' => 'Governance',
                'category' => 'Test',
                'element_number' => 1,
                'element_name' => 'Test element',
                'question' => 'Question '.$number,
            ]);
        }

        $this->actingAs($assessor)->post('/assessments')->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->actingAs($assessor)
            ->put('/assessments/'.$assessment->id, ['answers' => [1 => 'Yes', 2 => 'Yes', 3 => 'No']])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('assessment_element_results', [
            'assessment_id' => $assessment->id,
            'element_number' => 1,
            'yes_count' => 2,
            'maturity_score' => 2,
        ]);
        $this->actingAs($assessor)
            ->get('/assessments/'.$assessment->id)
            ->assertOk()
            ->assertSee('All 33 element results')
            ->assertSee('Test element');
        $this->actingAs($assessor)
            ->get('/assessments/'.$assessment->id.'/report')
            ->assertDownload('ncsbas-assessment-'.$assessment->id.'.pdf');
    }

    public function test_only_assigned_reviewer_can_open_assessment_and_complete_review(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $otherReviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);

        $this->actingAs($assessor)
            ->post('/assessments/'.$assessment->id.'/reviews', ['reviewer_id' => $reviewer->id])
            ->assertSessionHas('status');
        $review = Review::firstOrFail();
        $this->actingAs($assessor)->put('/assessments/'.$assessment->id, ['answers' => []])->assertStatus(422);
        $this->actingAs($assessor)->get('/reviews')->assertForbidden();
        $this->actingAs($reviewer)
            ->get('/reviews')
            ->assertOk()
            ->assertSee('Reviewer Module')
            ->assertSee('Open review');
        $this->actingAs($reviewer)
            ->get('/reviews/'.$review->id)
            ->assertOk()
            ->assertSee('Review actions')
            ->assertSee('Assessment responses');
        $this->actingAs($admin)->get('/reviews')->assertOk()->assertSee('Reviewer Module');
        $this->actingAs($otherReviewer)->get('/assessments/'.$assessment->id)->assertForbidden();
        $this->actingAs($reviewer)->get('/assessments/'.$assessment->id)->assertOk()->assertSee('Review by');
        $this->actingAs($reviewer)->patch('/reviews/'.$review->id, ['action' => 'accept'])->assertSessionHas('status');
        $this->actingAs($reviewer)
            ->post('/reviews/'.$review->id.'/comments', ['body' => 'Please confirm the evidence.'])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('review_comments', ['review_id' => $review->id, 'body' => 'Please confirm the evidence.']);
        $this->actingAs($reviewer)
            ->get('/reviews/'.$review->id)
            ->assertOk()
            ->assertSee('Please confirm the evidence.')
            ->assertSee($reviewer->name);
        $this->actingAs($reviewer)->patch('/reviews/'.$review->id, ['action' => 'complete'])->assertSessionHas('status');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'completed']);
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'status' => 'completed']);
    }

    public function test_declined_review_returns_to_open_for_admin_reassignment(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $replacement = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);

        $this->actingAs($assessor)
            ->post('/assessments/'.$assessment->id.'/reviews', ['reviewer_id' => $reviewer->id])
            ->assertSessionHas('status');
        $review = Review::firstOrFail();

        $this->actingAs($reviewer)
            ->patch('/reviews/'.$review->id, [
                'action' => 'decline',
                'decline_reason' => 'Conflict of interest.',
            ])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'status' => 'open']);
        $this->actingAs($reviewer)->get('/reviews/'.$review->id)->assertForbidden();

        $this->actingAs($admin)
            ->post('/assessments/'.$assessment->id.'/reviews', ['reviewer_id' => $replacement->id])
            ->assertSessionHas('status');
        $this->assertDatabaseCount('reviews', 2);
        $this->assertDatabaseHas('reviews', [
            'assessment_id' => $assessment->id,
            'reviewer_id' => $replacement->id,
            'status' => 'pending',
        ]);
    }

    public function test_dashboard_data_is_scoped_by_role(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $ownAssessment = Assessment::create(['user_id' => $assessor->id]);
        $otherAssessment = Assessment::create(['user_id' => $reviewer->id]);
        Review::create([
            'assessment_id' => $otherAssessment->id,
            'reviewer_id' => $reviewer->id,
            'requested_by_user_id' => $assessor->id,
            'status' => 'pending',
        ]);

        $this->actingAs($assessor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Assessment #'.$ownAssessment->id)
            ->assertDontSee('Assessment #'.$otherAssessment->id)
            ->assertDontSee('Reviewer Module');
        $this->actingAs($reviewer)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Assessment #'.$otherAssessment->id)
            ->assertDontSee('Assessment #'.$ownAssessment->id)
            ->assertSee('Reviewer Module');
        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Assessment #'.$ownAssessment->id)
            ->assertSee('Assessment #'.$otherAssessment->id)
            ->assertSee('Reviewer Module');
    }
}
