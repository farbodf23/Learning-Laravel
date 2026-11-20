<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('limits staff task visibility to their own assigned tasks', function () {
    // Create a store and two staff members.
    $store = Store::factory()->create();

    $staffA = User::factory()->create();
    $staffB = User::factory()->create();

    // Both users belong to the same store.
    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $staffA->id,
        'role' => 'staff',
    ]);

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $staffB->id,
        'role' => 'staff',
    ]);

    // Create a task assigned to Staff A.
    $ownTask = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Staff A task',
        'description' => 'Assigned to Staff A.',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $staffA->id,
        'assigned_to' => $staffA->id,
    ]);

    // Create a task assigned to Staff B.
    $otherTask = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Staff B task',
        'description' => 'Assigned to Staff B.',
        'status' => 'todo',
        'priority' => 'high',
        'created_by' => $staffA->id,
        'assigned_to' => $staffB->id,
    ]);

    // Create an unassigned task.
    $unassignedTask = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Unassigned task',
        'description' => 'Not assigned to anyone.',
        'status' => 'todo',
        'priority' => 'low',
        'created_by' => $staffA->id,
        'assigned_to' => null,
    ]);

    // Authenticate as Staff A.
    Sanctum::actingAs($staffA);

    // 1. Staff A should see only their own task.
    $this->getJson("/api/v1/stores/{$store->id}/tasks")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownTask->id);

    // 2. Staff A must not see Staff B's task by changing assigned_to.
    $this->getJson(
        "/api/v1/stores/{$store->id}/tasks?assigned_to={$staffB->id}"
    )
        ->assertOk()
        ->assertJsonCount(0, 'data');

    // 3. Even when filtering by their own ID, Staff A sees only their task.
    $this->getJson(
        "/api/v1/stores/{$store->id}/tasks?assigned_to={$staffA->id}"
    )
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $ownTask->id);

    // Verify that the other tasks still exist in the database.
    $this->assertDatabaseHas('tasks', ['id' => $otherTask->id]);
    $this->assertDatabaseHas('tasks', ['id' => $unassignedTask->id]);
});
