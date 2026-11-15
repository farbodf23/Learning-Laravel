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
        DB::statement('
        ALTER TABLE tasks
        ADD CONSTRAINT tasks_title_length_check
        CHECK (
            char_length(btrim(title)) BETWEEN 3 AND 150
        )
    ');
    }

    public function down(): void
    {
        DB::statement('
        ALTER TABLE tasks
        DROP CONSTRAINT tasks_title_length_check
    ');
    }
};
