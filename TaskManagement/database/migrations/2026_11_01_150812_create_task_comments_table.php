<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('task_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('body');

            $table->timestampTz('created_at')->useCurrent();

            $table->index(['store_id', 'task_id', 'created_at', 'id']);
        });
        DB::statement('
    ALTER TABLE task_comments
    ADD CONSTRAINT comments_task_store_fk
    FOREIGN KEY (store_id, task_id)
    REFERENCES tasks (store_id, id)
    ON DELETE RESTRICT
');

        DB::statement('
    ALTER TABLE task_comments
    ADD CONSTRAINT comments_author_membership_fk
    FOREIGN KEY (store_id, user_id)
    REFERENCES store_memberships (store_id, user_id)
    ON DELETE RESTRICT
');
    }

    public function down(): void
    {
        Schema::dropIfExists('task_comments');
    }
};
