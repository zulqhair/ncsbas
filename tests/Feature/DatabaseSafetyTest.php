<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSafetyTest extends TestCase
{
    public function test_destructive_database_commands_are_blocked_for_non_test_databases(): void
    {
        $database = config('database.connections.pgsql.database');
        config()->set('database.connections.pgsql.database', 'ncsbas');
        (new AppServiceProvider($this->app))->boot();

        try {
            $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('prohibited', Artisan::output());
        } finally {
            config()->set('database.connections.pgsql.database', $database);
            DB::prohibitDestructiveCommands(false);
        }
    }
}
