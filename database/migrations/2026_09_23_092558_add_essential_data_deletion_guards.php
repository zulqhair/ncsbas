<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
            CREATE OR REPLACE FUNCTION prevent_essential_data_deletion()
            RETURNS trigger
            LANGUAGE plpgsql
            AS $$
            BEGIN
                RAISE EXCEPTION \'Deleting records from the essential table "%" is prohibited.\', TG_TABLE_NAME;
            END;
            $$;
        ');

        foreach (['users', 'ncsb_questions', 'assessments', 'reviews'] as $table) {
            DB::unprepared(
                "CREATE TRIGGER prevent_{$table}_deletion BEFORE DELETE OR TRUNCATE ON {$table} FOR EACH STATEMENT EXECUTE FUNCTION prevent_essential_data_deletion();"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['users', 'ncsb_questions', 'assessments', 'reviews'] as $table) {
            DB::unprepared("DROP TRIGGER IF EXISTS prevent_{$table}_deletion ON {$table};");
        }

        DB::unprepared('DROP FUNCTION IF EXISTS prevent_essential_data_deletion();');
    }
};
