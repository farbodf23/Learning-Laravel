<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('task_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('actor_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('event_type', 50);
            $table->jsonb('changes')->nullable();

            $table->timestampTz('created_at')->useCurrent();

            $table->index(['store_id', 'task_id', 'created_at', 'id']);
        });
        DB::statement('
    ALTER TABLE task_events
    ADD CONSTRAINT events_task_store_fk
    FOREIGN KEY (store_id, task_id)
    REFERENCES tasks (store_id, id)
    ON DELETE RESTRICT
');

        DB::statement('
    ALTER TABLE task_events
    ADD CONSTRAINT events_actor_membership_fk
    FOREIGN KEY (store_id, actor_id)
    REFERENCES store_memberships (store_id, user_id)
    ON DELETE RESTRICT
');
    }

    public function down(): void
    {
        Schema::dropIfExists('task_events');
    }
};
