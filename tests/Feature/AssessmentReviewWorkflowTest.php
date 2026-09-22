<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentElementResult;
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
            ->assertDontSee('All 33 element results')
            ->assertDontSee('Reviewer assignment')
            ->assertSee('Submit Assessment')
            ->assertSee('Submit assessment for review')
            ->assertSee('Test element');
        $this->actingAs($assessor)
            ->get('/assessments/'.$assessment->id.'/report')
            ->assertDownload('ncsbas-assessment-'.$assessment->id.'.pdf');
    }

    public function test_no_answer_skips_the_remaining_questions_in_that_element(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $questions = collect([
            [1, 1],
            [2, 1],
            [3, 1],
            [4, 2],
        ])->mapWithKeys(function (array $question): array {
            [$number, $element] = $question;

            return [
                $number => NcsbQuestion::create([
                    'number' => $number,
                    'domain' => 'Governance',
                    'category' => 'Test',
                    'element_number' => $element,
                    'element_name' => 'Element '.$element,
                    'question' => 'Question '.$number,
                ]),
            ];
        });

        $this->actingAs($assessor)->post('/assessments')->assertRedirect();
        $assessment = Assessment::firstOrFail();

        $this->actingAs($assessor)
            ->post('/assessments/'.$assessment->id.'/reviews', ['reviewer_id' => $reviewer->id])
            ->assertSessionHasErrors('assessment');

        $this->actingAs($assessor)
            ->put('/assessments/'.$assessment->id, [
                'answers' => [
                    $questions[1]->id => 'No',
                    $questions[2]->id => 'Yes',
                ],
            ])
            ->assertSessionHasErrors();

        $this->assertDatabaseMissing('assessment_responses', [
            'assessment_id' => $assessment->id,
        ]);

        $this->actingAs($assessor)
            ->put('/assessments/'.$assessment->id, [
                'answers' => [
                    $questions[1]->id => 'No',
                    $questions[4]->id => 'Yes',
                ],
            ])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[1]->id,
            'answer' => 'No',
        ]);
        $this->assertDatabaseHas('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[4]->id,
            'answer' => 'Yes',
        ]);
        $this->assertDatabaseMissing('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[2]->id,
        ]);
        $this->assertDatabaseMissing('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $questions[3]->id,
        ]);

        $this->actingAs($assessor)
            ->post('/assessments/'.$assessment->id.'/reviews', ['reviewer_id' => $reviewer->id])
            ->assertSessionHas('status');
        $this->assertDatabaseHas('reviews', [
            'assessment_id' => $assessment->id,
            'reviewer_id' => $reviewer->id,
            'status' => 'pending',
        ]);
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
            ->assertSee('Accept review')
            ->assertDontSee('Open review');
        $this->actingAs($reviewer)
            ->get('/reviews/'.$review->id)
            ->assertForbidden();
        $this->actingAs($reviewer)
            ->get('/reviews/'.$review->id.'/responses')
            ->assertForbidden();
        $this->actingAs($admin)->get('/reviews')->assertOk()->assertSee('Reviewer Module');
        $this->actingAs($otherReviewer)->get('/assessments/'.$assessment->id)->assertForbidden();
        $this->actingAs($reviewer)->get('/assessments/'.$assessment->id)->assertForbidden();
        $this->actingAs($reviewer)->get('/assessments/'.$assessment->id.'/report')->assertForbidden();
        $this->actingAs($reviewer)->patch('/reviews/'.$review->id, ['action' => 'accept'])->assertSessionHas('status');
        $this->actingAs($reviewer)
            ->get('/reviews/'.$review->id)
            ->assertOk()
            ->assertSee('Review actions')
            ->assertSee('Domain maturity profile')
            ->assertSee('Maturity distribution')
            ->assertSee('Elements needing attention')
            ->assertSee('Element maturity scores')
            ->assertDontSee('33 element results')
            ->assertSee(route('reviews.responses', $review), false)
            ->assertSee('maturityLevel')
            ->assertSee('yesCount')
            ->assertDontSee('All assessment responses');
        $this->actingAs($reviewer)
            ->get('/reviews/'.$review->id.'/responses')
            ->assertOk()
            ->assertSee('Assessment responses')
            ->assertSee('This response record is read only.');
        $this->actingAs($reviewer)->get('/assessments/'.$assessment->id)->assertOk()->assertSee('Review by');
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

    public function test_reviewer_workspace_aggregates_element_scores_by_domain(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);
        $review = Review::create([
            'assessment_id' => $assessment->id,
            'reviewer_id' => $reviewer->id,
            'requested_by_user_id' => $assessor->id,
            'status' => 'accepted',
        ]);

        foreach ([
            [1, 'Govern', 'Policy management', 1],
            [2, 'Govern', 'Risk management', 3],
            [3, 'Identify', 'Asset management', 2],
        ] as [$number, $domain, $elementName, $score]) {
            NcsbQuestion::create([
                'number' => $number,
                'domain' => $domain,
                'category' => 'Test',
                'element_number' => $number,
                'element_name' => $elementName,
                'question' => 'Question '.$number,
            ]);
            AssessmentElementResult::create([
                'assessment_id' => $assessment->id,
                'element_number' => $number,
                'element_name' => $elementName,
                'yes_count' => $score,
                'maturity_score' => $score,
                'maturity_level' => 'Test level',
            ]);
        }

        $this->actingAs($reviewer)
            ->get('/reviews/'.$review->id)
            ->assertOk()
            ->assertSee('Domain maturity profile')
            ->assertSee('Element maturity scores')
            ->assertSee('Maturity distribution')
            ->assertSee('Elements needing attention')
            ->assertSee('Govern')
            ->assertSee('Identify')
            ->assertSeeInOrder(['\\u0022name\\u0022:\\u0022Govern\\u0022', '\\u0022score\\u0022:2', '\\u0022elementCount\\u0022:2'], false)
            ->assertSee('maturityDistribution')
            ->assertSee('lowestElements')
            ->assertSee('\\u0022count\\u0022:30', false);
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

    public function test_dashboard_does_not_display_the_recent_assessments_section(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        Assessment::create(['user_id' => $assessor->id]);

        $this->actingAs($assessor)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Recent assessments');
    }
}
