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
        $assessment = Assessment::create(['user_id' => $assessor->id]);

        $this->actingAs($assessor)
            ->post('/assessments/'.$assessment->id.'/reviews', ['reviewer_id' => $reviewer->id])
            ->assertSessionHas('status');
        $review = Review::firstOrFail();
        $this->actingAs($assessor)->put('/assessments/'.$assessment->id, ['answers' => []])->assertStatus(422);
        $this->actingAs($otherReviewer)->get('/assessments/'.$assessment->id)->assertForbidden();
        $this->actingAs($reviewer)->get('/assessments/'.$assessment->id)->assertOk()->assertSee('Review by');
        $this->actingAs($reviewer)->patch('/reviews/'.$review->id, ['action' => 'accept'])->assertSessionHas('status');
        $this->actingAs($reviewer)
            ->post('/reviews/'.$review->id.'/comments', ['body' => 'Please confirm the evidence.'])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('review_comments', ['review_id' => $review->id, 'body' => 'Please confirm the evidence.']);
        $this->actingAs($reviewer)->patch('/reviews/'.$review->id, ['action' => 'complete'])->assertSessionHas('status');
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'completed']);
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'status' => 'completed']);
    }
}
