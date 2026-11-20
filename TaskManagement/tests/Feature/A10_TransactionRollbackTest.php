
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\TaskEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

it('rolls back the task modification when event history cannot be saved', function () {
    // 1. Arrange: create a manager, store, and task.
    $manager = User::factory()->create();
    $store = Store::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    Sanctum::actingAs($manager);

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'created_by' => $manager->id,
        'assigned_to' => null,
        'title' => 'Prepare monthly report',
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'due_at' => null,
        'completed_at' => null,
    ]);

    // 2. Force PostgreSQL to reject status_changed events.
    DB::statement("
        ALTER TABLE task_events
        ADD CONSTRAINT test_reject_status_changed_events
        CHECK (event_type <> 'status_changed')
    ");

    // 3. Attempt to change the task status.
    $response = $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'in_progress']
    );

    // 4. The event insertion should fail.
    $response->assertServerError();

    // 5. Verify that the task modification was rolled back.
    $task->refresh();

    expect($task->status)->toBe('todo')
        ->and($task->completed_at)->toBeNull();

    // 6. Verify that no event was saved.
    expect(
        TaskEvent::where('task_id', $task->id)->count()
    )->toBe(0);
});
