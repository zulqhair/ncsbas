<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use App\Models\Review;
use App\Models\RoleModulePermission;
use App\Models\User;
use App\Services\NcsbScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            [User::ROLE_ASSESSOR, RoleModulePermission::MODULE_ASSESSOR],
            [User::ROLE_REVIEWER, RoleModulePermission::MODULE_ASSESSOR],
            [User::ROLE_REVIEWER, RoleModulePermission::MODULE_REVIEWER],
        ] as [$role, $module]) {
            RoleModulePermission::query()->firstOrCreate(compact('role', 'module'));
        }
    }

    public function test_public_pages_offer_real_account_actions_and_accessible_navigation(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Skip to main content')
            ->assertSee('Assessment process')
            ->assertSee('Register as Assessor')
            ->assertSee('Workspace roles')
            ->assertDontSee('User management');

        $this->get('/login')->assertOk()->assertSee('autocomplete="current-password"', false);
        $this->get('/register')->assertOk()->assertSee('New accounts are registered as Assessors.');
    }

    public function test_dashboard_displays_charts_for_the_current_users_assessments(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        Assessment::create([
            'user_id' => $assessor->id,
            'status' => 'completed',
            'overall_score' => 0.5,
            'overall_maturity_level' => 'Intermediate',
        ]);

        $this->actingAs($assessor)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('View status data')
            ->assertDontSee('View maturity data')
            ->assertDontSee('View assessment score data')
            ->assertDontSee('View all element scores')
            ->assertSee('50.0%')
            ->assertSee('id="assessmentScoresChart"', false)
            ->assertDontSee('No assessments to display yet');
    }

    public function test_empty_workspaces_explain_the_next_step_without_empty_charts(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);

        $this->actingAs($assessor)->get('/dashboard')
            ->assertOk()
            ->assertSee('No assessments to display yet')
            ->assertDontSee('<canvas', false);
        $this->get('/assessments')->assertOk()->assertSee('No assessments yet.');
    }

    public function test_failed_questionnaire_validation_restores_the_submitted_answers(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $assessment = Assessment::create(['user_id' => $assessor->id]);
        $assessment->details()->create(['label' => 'Organisation name', 'value' => 'NCSB Agency']);
        $question = NcsbQuestion::create([
            'number' => 1,
            'domain' => 'Govern',
            'category' => 'Policy',
            'element_number' => 1,
            'element_name' => 'Policy management',
            'question' => 'Is a policy in place?',
        ]);
        $assessment->responses()->create(['ncsb_question_id' => $question->id, 'answer' => 'No']);

        $this->actingAs($assessor)->from('/assessments/'.$assessment->id)
            ->put('/assessments/'.$assessment->id, ['answers' => [$question->id => 'Yes', 999 => 'Yes']])
            ->assertSessionHasErrors();

        $this->withCookie(config('session.cookie'), session()->getId())
            ->get('/assessments/'.$assessment->id)
            ->assertOk()
            ->assertSee('One or more submitted questions are invalid.')
            ->assertSee('value="Yes" checked', false)
            ->assertSee('data-restored-input="true"', false);
        $this->assertDatabaseHas('assessment_responses', [
            'assessment_id' => $assessment->id,
            'ncsb_question_id' => $question->id,
            'answer' => 'No',
        ]);
    }

    public function test_locked_assessments_offer_admin_reassignment_but_no_editable_questionnaire(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $assessment = Assessment::create(['user_id' => $assessor->id, 'status' => 'open']);
        $assessment->details()->create(['label' => 'Organisation name', 'value' => 'NCSB Agency']);

        $this->actingAs($admin)->get('/assessments/'.$assessment->id)
            ->assertOk()
            ->assertSee('Assign a reviewer')
            ->assertSee('name="reviewer_id"', false)
            ->assertDontSee('data-sequential-questionnaire', false);

        $this->actingAs($assessor)->get('/assessments/'.$assessment->id)
            ->assertOk()
            ->assertSee('This assessment is read only.')
            ->assertDontSee('name="reviewer_id"', false);
    }

    public function test_review_actions_follow_the_current_review_state(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $assessment = Assessment::create(['user_id' => $assessor->id, 'status' => 'open']);
        $review = Review::create([
            'assessment_id' => $assessment->id,
            'reviewer_id' => $reviewer->id,
            'requested_by_user_id' => $assessor->id,
            'status' => 'pending',
        ]);

        $this->actingAs($reviewer)->get('/reviews')
            ->assertOk()
            ->assertSee('Accept review');

        $this->patch('/reviews/'.$review->id, ['action' => 'accept'])->assertSessionHas('status');
        $this->get('/reviews/'.$review->id)
            ->assertSee('value="complete"', false)
            ->assertDontSee('value="accept"', false)
            ->assertSee('Decline this review');

        $this->patch('/reviews/'.$review->id, ['action' => 'complete'])->assertSessionHas('status');
        $this->get('/reviews/'.$review->id)
            ->assertSee('This review is complete.')
            ->assertDontSee('value="complete"', false)
            ->assertDontSee('Decline this review')
            ->assertSee('Add a review comment');
    }

    public function test_role_workspaces_render_their_controls_and_optional_visual_fixtures(): void
    {
        $assessor = User::factory()->create(['name' => 'Amina Assessment', 'role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['name' => 'Rashid Review', 'role' => User::ROLE_REVIEWER]);
        $admin = User::factory()->create(['name' => 'System Administrator', 'role' => User::ROLE_ADMIN]);
        $draft = Assessment::create(['user_id' => $assessor->id]);
        $draft->details()->create(['label' => 'Organisation name', 'value' => 'NCSB Agency']);

        foreach (range(1, 33) as $element) {
            foreach (range(1, 3) as $index) {
                $question = NcsbQuestion::create([
                    'number' => ($element - 1) * 3 + $index,
                    'domain' => 'Govern',
                    'category' => 'Cyber security assurance',
                    'element_number' => $element,
                    'element_name' => 'Cyber security policies and organisational responsibilities '.$element,
                    'question' => 'Are the cyber security responsibilities documented, communicated and regularly reviewed?',
                ]);
                $draft->responses()->create(['ncsb_question_id' => $question->id, 'answer' => $index < 3 ? 'Yes' : 'No']);
            }
        }
        app(NcsbScoringService::class)->calculate($draft);
        $assigned = Assessment::create(['user_id' => $assessor->id, 'status' => 'open']);
        $review = Review::create([
            'assessment_id' => $assigned->id,
            'reviewer_id' => $reviewer->id,
            'requested_by_user_id' => $assessor->id,
            'status' => 'accepted',
        ]);

        foreach (['dashboard' => '/dashboard', 'assessments' => '/assessments', 'questionnaire' => '/assessments/'.$draft->id] as $name => $path) {
            $response = $this->actingAs($assessor)->get($path)->assertOk();
            $response->assertSee('Assessor Module')->assertDontSee('User management');
            $this->capturePreview($name, $response->getContent());
        }
        foreach (['reviews' => '/reviews', 'review-detail' => '/reviews/'.$review->id] as $name => $path) {
            $response = $this->actingAs($reviewer)->get($path)->assertOk();
            $response->assertSee('Reviewer Module')->assertDontSee('User management');
            $this->capturePreview($name, $response->getContent());
        }
        $response = $this->actingAs($admin)->get('/admin/users')->assertOk();
        $response->assertSee('Role for Amina Assessment')->assertSee('Save role for Amina Assessment');
        $this->capturePreview('users', $response->getContent());
    }

    private function capturePreview(string $name, string $html): void
    {
        $directory = getenv('NCSBAS_PREVIEW_DIR');

        if (is_string($directory) && is_dir($directory)) {
            file_put_contents($directory.DIRECTORY_SEPARATOR.$name.'.html', $html);
        }
    }
}
