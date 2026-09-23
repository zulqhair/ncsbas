<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Review;
use App\Models\RoleModulePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModulePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_module_access_preserves_the_current_role_workspaces(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);

        $this->assertDatabaseHas('role_module_permissions', [
            'role' => User::ROLE_ASSESSOR,
            'module' => RoleModulePermission::MODULE_ASSESSOR,
        ]);
        $this->assertDatabaseHas('role_module_permissions', [
            'role' => User::ROLE_REVIEWER,
            'module' => RoleModulePermission::MODULE_ASSESSOR,
        ]);
        $this->assertDatabaseHas('role_module_permissions', [
            'role' => User::ROLE_REVIEWER,
            'module' => RoleModulePermission::MODULE_REVIEWER,
        ]);

        $this->actingAs($assessor)->get('/assessments')->assertOk();
        $this->actingAs($reviewer)->get('/assessments')->assertOk();
        $this->get('/reviews')->assertOk();
    }

    public function test_only_administrators_can_manage_module_access(): void
    {
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);

        $this->actingAs($assessor)
            ->get('/admin/module-access')
            ->assertForbidden();
    }

    public function test_assessor_module_only_lists_assessments_created_by_the_authenticated_user(): void
    {
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);
        $otherUser = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $ownAssessment = Assessment::create(['user_id' => $reviewer->id]);
        $assignedAssessment = Assessment::create(['user_id' => $otherUser->id]);
        Review::create([
            'assessment_id' => $assignedAssessment->id,
            'reviewer_id' => $reviewer->id,
            'requested_by_user_id' => $otherUser->id,
            'status' => 'accepted',
        ]);

        $this->actingAs($reviewer)
            ->get('/assessments')
            ->assertOk()
            ->assertSee(route('assessments.show', $ownAssessment), false)
            ->assertDontSee(route('assessments.show', $assignedAssessment), false);
    }

    public function test_an_administrator_can_configure_module_access_by_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $assessor = User::factory()->create(['role' => User::ROLE_ASSESSOR]);
        $reviewer = User::factory()->create(['role' => User::ROLE_REVIEWER]);

        $this->actingAs($admin)
            ->get('/admin/module-access')
            ->assertOk()
            ->assertSee('Module access')
            ->assertSee('module-assessor-assessor', false)
            ->assertSee('module-reviewer-reviewer', false);

        $this->put('/admin/module-access', [
            'modules' => [
                User::ROLE_REVIEWER => [RoleModulePermission::MODULE_REVIEWER],
            ],
        ])->assertSessionHas('status');

        $this->assertDatabaseMissing('role_module_permissions', [
            'role' => User::ROLE_ASSESSOR,
            'module' => RoleModulePermission::MODULE_ASSESSOR,
        ]);
        $this->assertDatabaseHas('role_module_permissions', [
            'role' => User::ROLE_REVIEWER,
            'module' => RoleModulePermission::MODULE_REVIEWER,
        ]);
        $this->assertDatabaseMissing('role_module_permissions', [
            'role' => User::ROLE_REVIEWER,
            'module' => RoleModulePermission::MODULE_ASSESSOR,
        ]);

        $this->actingAs($assessor)
            ->get('/dashboard')
            ->assertDontSee('Assessor Module')
            ->assertDontSee('Reviewer Module')
            ->assertDontSee('New assessment');
        $this->get('/assessments')->assertForbidden();

        $this->actingAs($reviewer)
            ->get('/dashboard')
            ->assertSee('Reviewer Module')
            ->assertDontSee('Assessor Module');
        $this->get('/reviews')->assertOk();
        $this->get('/assessments')->assertForbidden();

        $this->actingAs($admin)
            ->get('/assessments')
            ->assertOk();
        $this->get('/reviews')->assertOk();
    }
}
