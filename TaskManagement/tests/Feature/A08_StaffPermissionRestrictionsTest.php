<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('denies staff members access to manager-only operations', function () {
    // Create a store and its manager and staff member.
    $store = Store::factory()->create();

    $manager = User::factory()->create();
    $staff = User::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $staff->id,
        'role' => 'staff',
    ]);

    // Create a task that is visible to the staff member.
    $todoTask = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Prepare monthly report',
        'description' => 'Prepare the monthly financial report.',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => $staff->id,
        'due_at' => null,
        'completed_at' => null,
    ]);

    // Create a completed task for the reopening test.
    $completedTask = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Review annual budget',
        'description' => 'Review and finalize the annual budget.',
        'status' => 'done',
        'priority' => 'high',
        'created_by' => $manager->id,
        'assigned_to' => $staff->id,
        'due_at' => null,
        'completed_at' => now()->subHour(),
    ]);

    // Authenticate as the staff member.
    Sanctum::actingAs($staff);

    // 1. Staff cannot create tasks.
    $this->postJson("/api/v1/stores/{$store->id}/tasks", [
        'title' => 'Unauthorized task',
        'description' => 'This task should not be created.',
        'priority' => 'medium',
    ])->assertForbidden();

    // 2. Staff cannot edit task specifications.
    $this->patchJson(
        "/api/v1/stores/{$store->id}/tasks/{$todoTask->id}",
        [
            'title' => 'Unauthorized title change',
        ]
    )->assertForbidden();

    // 3. Staff cannot cancel a task.
    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$todoTask->id}/status",
        [
            'status' => 'cancelled',
        ]
    )->assertForbidden();

    // 4. Staff cannot reopen a completed task.
    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$completedTask->id}/status",
        [
            'status' => 'todo',
        ]
    )->assertForbidden();

    // 5. Staff cannot access the task summary.
    $this->getJson("/api/v1/stores/{$store->id}/task-summary")
        ->assertForbidden();

    // Verify that the task specifications and statuses were not changed.
    $this->assertDatabaseHas('tasks', [
        'id' => $todoTask->id,
        'title' => 'Prepare monthly report',
        'status' => 'todo',
    ]);

    $this->assertDatabaseHas('tasks', [
        'id' => $completedTask->id,
        'status' => 'done',
    ]);

    // Verify that no additional task was created.
    expect(Task::count())->toBe(2);
});
