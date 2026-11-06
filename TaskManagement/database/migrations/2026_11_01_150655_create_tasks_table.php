<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('title', 150);
            $table->text('description')->nullable();

            $table->string('status', 20)->default('todo');
            $table->string('priority', 20)->default('medium');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['store_id', 'id']);

            $table->index(['store_id', 'status', 'created_at', 'id']);
            $table->index(['store_id', 'assigned_to', 'status']);
            $table->index(['store_id', 'due_at']);
        });
        DB::statement("
    ALTER TABLE tasks
    ADD CONSTRAINT tasks_status_check
    CHECK (status IN ('todo', 'in_progress', 'done', 'cancelled'))
");

        DB::statement("
    ALTER TABLE tasks
    ADD CONSTRAINT tasks_priority_check
    CHECK (priority IN ('low', 'medium', 'high'))
");

        DB::statement("
    ALTER TABLE tasks
    ADD CONSTRAINT tasks_completed_at_check
    CHECK (
        (status = 'done' AND completed_at IS NOT NULL)
        OR
        (status <> 'done' AND completed_at IS NULL)
    )
");
        DB::statement('
    ALTER TABLE tasks
    ADD CONSTRAINT tasks_creator_membership_fk
    FOREIGN KEY (store_id, created_by)
    REFERENCES store_memberships (store_id, user_id)
    ON DELETE RESTRICT
');

        DB::statement('
    ALTER TABLE tasks
    ADD CONSTRAINT tasks_assignee_membership_fk
    FOREIGN KEY (store_id, assigned_to)
    REFERENCES store_memberships (store_id, user_id)
    ON DELETE RESTRICT
');
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
