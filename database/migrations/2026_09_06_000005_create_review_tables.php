<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('requested_by_user_id')->constrained('users');
            $t->string('status')->default('pending');
            $t->text('decline_reason')->nullable();
            $t->timestamps();
        });
        Schema::create('review_comments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('review_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_comments');
        Schema::dropIfExists('reviews');
    }
};
