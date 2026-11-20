<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createManagerAndStoreForA07(): array
{
    $store = Store::factory()->create();
    $manager = User::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    Sanctum::actingAs($manager);

    return [
        'store' => $store,
        'manager' => $manager,
    ];
}

it('rejects a direct transition from todo to done', function () {
    $context = createManagerAndStoreForA07();

    $store = $context['store'];
    $manager = $context['manager'];

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Prepare monthly report',
        'description' => 'Prepare the monthly financial report.',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => null,
        'due_at' => null,
        'completed_at' => null,
    ]);

    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'done']
    )->assertStatus(409);

    // The rejected transition must not modify the task.
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'todo',
        'completed_at' => null,
    ]);
});

it('allows a manager to reopen a completed task and clears completed_at', function () {
    $context = createManagerAndStoreForA07();

    $store = $context['store'];
    $manager = $context['manager'];

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Prepare monthly report',
        'description' => 'Prepare the monthly financial report.',
        'status' => 'done',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => null,
        'due_at' => null,
        'completed_at' => now()->subHour(),
    ]);

    // Confirm that the task initially has a completion timestamp.
    expect($task->completed_at)->not->toBeNull();

    // Reopen the task: done -> todo.
    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'todo']
    )->assertOk();

    $task->refresh();

    expect($task->status)->toBe('todo')
        ->and($task->completed_at)->toBeNull();

    // Verify that the database also reflects the changes.
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'todo',
        'completed_at' => null,
    ]);
});
