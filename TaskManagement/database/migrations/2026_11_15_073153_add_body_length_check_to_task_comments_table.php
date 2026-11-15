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
        ALTER TABLE task_comments
        ADD CONSTRAINT comments_body_length_check
        CHECK (
            char_length(btrim(body)) BETWEEN 1 AND 2000
        )
    ');
    }

    public function down(): void
    {
        DB::statement('
        ALTER TABLE task_comments
        DROP CONSTRAINT comments_body_length_check
    ');
    }
};
