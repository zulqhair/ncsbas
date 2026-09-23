<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_module_permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('role');
            $table->string('module');
            $table->timestamps();

            $table->unique(['role', 'module']);
        });

        DB::table('role_module_permissions')->insert([
            [
                'role' => 'assessor',
                'module' => 'assessor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role' => 'reviewer',
                'module' => 'assessor',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role' => 'reviewer',
                'module' => 'reviewer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_module_permissions');
    }
};
