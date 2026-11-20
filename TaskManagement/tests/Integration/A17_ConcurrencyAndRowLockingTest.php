
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

it('prevents the previous assignee from changing status after reassignment and verifies row locking', function () {
    // Create two independent PostgreSQL connections.
    $primaryName = config('database.default');
    $secondaryName = 'pgsql_concurrent_a17';

    config([
        "database.connections.{$secondaryName}" => config("database.connections.{$primaryName}"),
    ]);

    DB::purge($secondaryName);

    $primary = DB::connection($primaryName);
    $secondary = DB::connection($secondaryName);

    // Create users and store.
    $manager = User::factory()->create();
    $oldAssignee = User::factory()->create();
    $newAssignee = User::factory()->create();

    $store = Store::factory()->create();

    foreach ([
        [$manager, 'manager'],
        [$oldAssignee, 'staff'],
        [$newAssignee, 'staff'],
    ] as [$user, $role]) {
        StoreMembership::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Concurrency test task',
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => $oldAssignee->id,
        'due_at' => null,
        'completed_at' => null,
    ]);

    try {
        // Connection A begins a transaction and locks the task.
        $primary->beginTransaction();

        $primary->table('tasks')
            ->where('id', $task->id)
            ->lockForUpdate()
            ->first();

        // The manager reassigns the task using the actual API.
        Sanctum::actingAs($manager);

        $this->putJson(
            "/api/v1/stores/{$store->id}/tasks/{$task->id}/assignee",
            ['assigned_to' => $newAssignee->id]
        )->assertOk();

        // Connection B attempts to lock the same row.
        // It should fail because connection A still holds the lock.
        $secondary->statement("SET lock_timeout = '200ms'");

        $lockWasBlocked = false;

        try {
            $secondary->table('tasks')
                ->where('id', $task->id)
                ->lockForUpdate()
                ->first();
        } catch (QueryException $exception) {
            $lockWasBlocked = str_contains(
                $exception->getMessage(),
                'lock timeout'
            ) || str_contains(
                $exception->getMessage(),
                '55P03'
            );
        }

        expect($lockWasBlocked)->toBeTrue();

        // Commit reassignment and release connection A's lock.
        $primary->commit();

        // The previous assignee must now be denied.
        Sanctum::actingAs($oldAssignee);

        $this->putJson(
            "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
            ['status' => 'in_progress']
        )->assertNotFound();

        // Verify that the task remains assigned to the new user
        // and its status has not changed.
        $task->refresh();

        expect($task->assigned_to)->toBe($newAssignee->id)
            ->and($task->status)->toBe('todo');

    } finally {
        // Do not leave an open transaction if an assertion fails.
        if ($primary->transactionLevel() > 0) {
            $primary->rollBack();
        }

        $secondary->statement('RESET lock_timeout');

        DB::purge($secondaryName);
    }
});
