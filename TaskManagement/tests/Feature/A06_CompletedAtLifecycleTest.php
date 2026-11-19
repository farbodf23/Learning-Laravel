<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('sets completed_at only when a task enters done', function () {
    // Create a store and its manager.
    $store = Store::factory()->create();
    $manager = User::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    // Create a task in the todo state.
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

    Sanctum::actingAs($manager);

    // Initially, completed_at must be NULL.
    expect($task->completed_at)->toBeNull();

    // Transition: todo -> in_progress.
    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'in_progress']
    )->assertOk();

    // Refresh the model to retrieve the latest database values.
    $task->refresh();

    expect($task->status)->toBe('in_progress')
        ->and($task->completed_at)->toBeNull();

    // Transition: in_progress -> done.
    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'done']
    )->assertOk();

    $task->refresh();

    expect($task->status)->toBe('done')
        ->and($task->completed_at)->not->toBeNull();

    // Verify the timestamp was actually persisted.
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'done',
    ]);

    expect($task->completed_at)
    ->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});