<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('prevents a member of one store from accessing another store tasks', function () {
    // Create two separate stores.
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    // Create users.
    $managerA = User::factory()->create();
    $staffB = User::factory()->create();

    // The manager belongs to Store A.
    StoreMembership::create([
        'store_id' => $storeA->id,
        'user_id' => $managerA->id,
        'role' => 'manager',
    ]);

    // The staff member belongs only to Store B.
    StoreMembership::create([
        'store_id' => $storeB->id,
        'user_id' => $staffB->id,
        'role' => 'staff',
    ]);

    // Create a task in Store A.
    // Its creator must be a member of Store A.
    $task = Task::forcecreate([
        'store_id' => $storeA->id,
        'title' => 'Store A confidential task',
        'description' => 'This task belongs exclusively to Store A.',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $managerA->id,
        'assigned_to' => null,
        'due_at' => null,
        'completed_at' => null,
    ]);

    // Authenticate as the staff member from Store B.
    Sanctum::actingAs($staffB);

    // 1. Attempt to view the task.
    $this->getJson("/api/v1/stores/{$storeA->id}/tasks/{$task->id}")
        ->assertNotFound();

    // 2. Attempt to update the task.
    $this->patchJson("/api/v1/stores/{$storeA->id}/tasks/{$task->id}", [
        'title' => 'Unauthorized modification',
    ])->assertNotFound();

    // 3. Attempt to view its comments.
    $this->getJson("/api/v1/stores/{$storeA->id}/tasks/{$task->id}/comments")
        ->assertNotFound();

    // 4. Attempt to create a comment.
    $this->postJson("/api/v1/stores/{$storeA->id}/tasks/{$task->id}/comments", [
        'body' => 'Unauthorized comment',
    ])->assertNotFound();

    // 5. Attempt to view its event history.
    $this->getJson("/api/v1/stores/{$storeA->id}/tasks/{$task->id}/events")
        ->assertNotFound();

    // Verify that the task was not modified.
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'title' => 'Store A confidential task',
        'store_id' => $storeA->id,
    ]);
});
