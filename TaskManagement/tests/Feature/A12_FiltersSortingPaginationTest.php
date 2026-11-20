
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createManagerAndStoreForA12(): array
{
    $manager = User::factory()->create();
    $store = Store::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    return [$manager, $store];
}

function createTaskForA12(
    Store $store,
    User $creator,
    ?int $assignee,
    array $attributes = []
): Task {
    return Task::forceCreate(array_merge([
        'store_id' => $store->id,
        'created_by' => $creator->id,
        'assigned_to' => $assignee,
        'title' => 'Test task',
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'due_at' => null,
        'completed_at' => null,
    ], $attributes));
}

it('applies combined filters using AND logic', function () {
    [$manager, $store] = createManagerAndStoreForA12();

    $staff = User::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $staff->id,
        'role' => 'staff',
    ]);

    $matchingTask = createTaskForA12(
        $store,
        $manager,
        $staff->id,
        [
            'title' => 'Matching task',
            'status' => 'todo',
            'priority' => 'high',
        ]
    );

    // Correct status, but wrong priority.
    createTaskForA12($store, $manager, $staff->id, [
        'status' => 'todo',
        'priority' => 'low',
    ]);

    // Correct priority, but wrong status.
    createTaskForA12($store, $manager, $staff->id, [
        'status' => 'done',
        'priority' => 'high',
        'completed_at' => now(),
    ]);

    // Correct status and priority, but wrong assignee.
    createTaskForA12($store, $manager, null, [
        'status' => 'todo',
        'priority' => 'high',
    ]);

    Sanctum::actingAs($manager);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks"
        ."?status=todo&priority=high&assigned_to={$staff->id}"
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingTask->id)
        ->assertJsonPath('meta.total', 1);
});

it('sorts tasks by created_at descending and then id descending', function () {
    [$manager, $store] = createManagerAndStoreForA12();

    $olderDate = now()->subDays(2);
    $newerDate = now()->subDay();
    $sameDate = now()->subHours(2);

    $olderTask = createTaskForA12($store, $manager, null, [
        'created_at' => $olderDate,
        'updated_at' => $olderDate,
    ]);

    $sameDateFirst = createTaskForA12($store, $manager, null, [
        'created_at' => $sameDate,
        'updated_at' => $sameDate,
    ]);

    $sameDateSecond = createTaskForA12($store, $manager, null, [
        'created_at' => $sameDate,
        'updated_at' => $sameDate,
    ]);

    $newerTask = createTaskForA12($store, $manager, null, [
        'created_at' => $newerDate,
        'updated_at' => $newerDate,
    ]);

    Sanctum::actingAs($manager);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks"
    );

    $response->assertOk();

    $ids = collect($response->json('data'))
        ->pluck('id')
        ->all();

    expect($ids)->toBe([
        $sameDateSecond->id,
        $sameDateFirst->id,
        $newerTask->id,
        $olderTask->id,
    ]);
});

it('paginates tasks and returns correct pagination metadata', function () {
    [$manager, $store] = createManagerAndStoreForA12();

    for ($i = 1; $i <= 25; $i++) {
        createTaskForA12($store, $manager, null, [
            'title' => "Task {$i}",
            'created_at' => now()->subMinutes($i),
            'updated_at' => now()->subMinutes($i),
        ]);
    }

    Sanctum::actingAs($manager);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks?per_page=10&page=2"
    );

    $response->assertOk()
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.page', 2)
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonPath('meta.total', 25);
});

it('does not include invisible tasks in staff pagination totals', function () {
    [$manager, $store] = createManagerAndStoreForA12();

    $staffA = User::factory()->create();
    $staffB = User::factory()->create();

    foreach ([$staffA, $staffB] as $staff) {
        StoreMembership::create([
            'store_id' => $store->id,
            'user_id' => $staff->id,
            'role' => 'staff',
        ]);
    }

    // Two tasks visible to Staff A.
    createTaskForA12($store, $manager, $staffA->id);
    createTaskForA12($store, $manager, $staffA->id);

    // Three tasks invisible to Staff A.
    createTaskForA12($store, $manager, $staffB->id);
    createTaskForA12($store, $manager, $staffB->id);
    createTaskForA12($store, $manager, null);

    Sanctum::actingAs($staffA);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks"
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

it('rejects a per_page value greater than 100', function () {
    [$manager, $store] = createManagerAndStoreForA12();

    Sanctum::actingAs($manager);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks?per_page=101"
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('per_page');
});
