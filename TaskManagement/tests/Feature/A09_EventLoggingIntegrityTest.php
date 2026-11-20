
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\TaskEvent;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createManagerAndStoreForA09(): array
{
    $manager = User::factory()->create();
    $store = Store::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    Sanctum::actingAs($manager);

    return [$manager, $store];
}

function createTaskForA09(Store $store, User $manager, array $attributes = []): Task
{
    return Task::forceCreate(array_merge([
        'store_id' => $store->id,
        'title' => 'Test task',
        'description' => 'Task for event logging tests',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => null,
        'due_at' => null,
        'completed_at' => null,
    ], $attributes));
}

it('logs exactly one event after a successful status change', function () {
    [$manager, $store] = createManagerAndStoreForA09();

    $task = createTaskForA09($store, $manager);

    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'in_progress']
    )->assertOk();

    $events = TaskEvent::where('task_id', $task->id)->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->event_type)->toBe('status_changed')
        ->and($events->first()->changes)->toBe([
            'before' => ['status' => 'todo'],
            'after' => ['status' => 'in_progress'],
        ]);
});

it('does not create an event when the requested status is unchanged', function () {
    [$manager, $store] = createManagerAndStoreForA09();

    $task = createTaskForA09($store, $manager, [
        'status' => 'in_progress',
    ]);

    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'in_progress']
    )->assertOk();

    expect(TaskEvent::where('task_id', $task->id)->count())->toBe(0);
});
