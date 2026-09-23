<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\NcsbQuestion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class EssentialDataDeletionGuardTest extends TestCase
{
    public function test_baseline_questions_cannot_be_deleted_or_truncated(): void
    {
        NcsbQuestion::create([
            'number' => 1,
            'domain' => 'Govern',
            'category' => 'Policy',
            'element_number' => 1,
            'element_name' => 'Policy management',
            'question' => 'Is a policy in place?',
        ]);

        $this->expectException(QueryException::class);

        NcsbQuestion::query()->delete();
    }

    public function test_users_and_assessments_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        Assessment::create(['user_id' => $user->id]);

        $this->expectException(QueryException::class);

        Assessment::query()->delete();
    }
}
