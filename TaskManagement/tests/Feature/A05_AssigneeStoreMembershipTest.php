<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('rejects assigning a task to a user outside the store', function () {
    // Create two stores.
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    // Create the manager, current assignee, and outside user.
    $manager = User::factory()->create();
    $currentAssignee = User::factory()->create();
    $outsideUser = User::factory()->create();

    // Manager and current assignee belong to Store A.
    StoreMembership::create([
        'store_id' => $storeA->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    StoreMembership::create([
        'store_id' => $storeA->id,
        'user_id' => $currentAssignee->id,
        'role' => 'staff',
    ]);

    // The outside user belongs only to Store B.
    StoreMembership::create([
        'store_id' => $storeB->id,
        'user_id' => $outsideUser->id,
        'role' => 'staff',
    ]);

    // Create a task in Store A assigned to its existing staff member.
    $task = Task::forceCreate([
        'store_id' => $storeA->id,
        'title' => 'Prepare monthly report',
        'description' => 'Prepare the monthly financial report.',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => $currentAssignee->id,
        'due_at' => null,
        'completed_at' => null,
    ]);

    // Authenticate as the manager of Store A.
    Sanctum::actingAs($manager);

    // Attempt to assign the task to someone outside Store A.
    $this->putJson(
        "/api/v1/stores/{$storeA->id}/tasks/{$task->id}/assignee",
        [
            'assigned_to' => $outsideUser->id,
        ]
    )
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['fields' => ['assigned_to']]]);

    // Verify that the original assignment remains unchanged.
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'store_id' => $storeA->id,
        'assigned_to' => $currentAssignee->id,
    ]);
});
