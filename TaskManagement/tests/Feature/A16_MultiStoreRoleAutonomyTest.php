
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createTaskForA16(
    Store $store,
    User $creator,
    ?User $assignee = null
): Task {
    return Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Task for '.$store->id,
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $creator->id,
        'assigned_to' => $assignee?->id,
        'due_at' => null,
        'completed_at' => null,
    ]);
}

it('evaluates the same user role independently in each store', function () {
    // Create two stores.
    $storeA = Store::factory()->create();
    $storeB = Store::factory()->create();

    // This user has different roles in the two stores.
    $user = User::factory()->create();

    // Other users needed to establish Store B's membership and tasks.
    $managerB = User::factory()->create();
    $staffB = User::factory()->create();

    StoreMembership::create([
        'store_id' => $storeA->id,
        'user_id' => $user->id,
        'role' => 'manager',
    ]);

    StoreMembership::create([
        'store_id' => $storeB->id,
        'user_id' => $user->id,
        'role' => 'staff',
    ]);

    StoreMembership::create([
        'store_id' => $storeB->id,
        'user_id' => $managerB->id,
        'role' => 'manager',
    ]);

    StoreMembership::create([
        'store_id' => $storeB->id,
        'user_id' => $staffB->id,
        'role' => 'staff',
    ]);

    // Create tasks in both stores.
    $taskA = createTaskForA16($storeA, $user);

    $assignedTaskB = createTaskForA16(
        $storeB,
        $managerB,
        $user
    );

    $otherStaffTaskB = createTaskForA16(
        $storeB,
        $managerB,
        $staffB
    );

    Sanctum::actingAs($user);

    // 1. The user is a manager in Store A and can create tasks there.
    $this->postJson("/api/v1/stores/{$storeA->id}/tasks", [
        'title' => 'Created in Store A',
    ])->assertCreated();

    // 2. The same user is only staff in Store B and cannot create tasks.
    $this->postJson("/api/v1/stores/{$storeB->id}/tasks", [
        'title' => 'Should not be created',
    ])->assertForbidden();

    // 3. As a manager, the user can see all tasks in Store A.
    $this->getJson("/api/v1/stores/{$storeA->id}/tasks")
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // 4. As staff, the user can see only their assigned tasks in Store B.
    $this->getJson("/api/v1/stores/{$storeB->id}/tasks")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'id' => $assignedTaskB->id,
        ])
        ->assertJsonMissing([
            'id' => $otherStaffTaskB->id,
        ]);

    // 5. The user can edit a task in Store A as its manager.
    $this->patchJson(
        "/api/v1/stores/{$storeA->id}/tasks/{$taskA->id}",
        ['title' => 'Updated in Store A']
    )->assertOk();

    // 6. The user cannot edit task specifications in Store B as staff.
    $this->patchJson(
        "/api/v1/stores/{$storeB->id}/tasks/{$assignedTaskB->id}",
        ['title' => 'Unauthorized update']
    )->assertForbidden();

    // 7. Manager-only summary access is also store-specific.
    $this->getJson("/api/v1/stores/{$storeA->id}/task-summary")
        ->assertOk();

    $this->getJson("/api/v1/stores/{$storeB->id}/task-summary")
        ->assertForbidden();
});
