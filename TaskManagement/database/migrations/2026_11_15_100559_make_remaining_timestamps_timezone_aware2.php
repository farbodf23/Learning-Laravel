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
        DB::statement("
        ALTER TABLE failed_jobs
        ALTER COLUMN failed_at TYPE timestamptz
        USING failed_at AT TIME ZONE 'UTC'
    ");

        DB::statement("
        ALTER TABLE password_reset_tokens
        ALTER COLUMN created_at TYPE timestamptz
        USING created_at AT TIME ZONE 'UTC'
    ");

        DB::statement("
        ALTER TABLE personal_access_tokens
        ALTER COLUMN created_at TYPE timestamptz
            USING created_at AT TIME ZONE 'UTC',
        ALTER COLUMN updated_at TYPE timestamptz
            USING updated_at AT TIME ZONE 'UTC',
        ALTER COLUMN expires_at TYPE timestamptz
            USING expires_at AT TIME ZONE 'UTC',
        ALTER COLUMN last_used_at TYPE timestamptz
            USING last_used_at AT TIME ZONE 'UTC'
    ");

        DB::statement("
        ALTER TABLE store_memberships
        ALTER COLUMN created_at TYPE timestamptz
            USING created_at AT TIME ZONE 'UTC',
        ALTER COLUMN updated_at TYPE timestamptz
            USING updated_at AT TIME ZONE 'UTC'
    ");

        DB::statement("
        ALTER TABLE stores
        ALTER COLUMN created_at TYPE timestamptz
            USING created_at AT TIME ZONE 'UTC',
        ALTER COLUMN updated_at TYPE timestamptz
            USING updated_at AT TIME ZONE 'UTC'
    ");

        DB::statement("
        ALTER TABLE users
        ALTER COLUMN created_at TYPE timestamptz
            USING created_at AT TIME ZONE 'UTC',
        ALTER COLUMN updated_at TYPE timestamptz
            USING updated_at AT TIME ZONE 'UTC',
        ALTER COLUMN email_verified_at TYPE timestamptz
            USING email_verified_at AT TIME ZONE 'UTC'
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
