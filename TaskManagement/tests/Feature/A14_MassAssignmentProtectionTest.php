
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createManagerAndStoreForA14(): array
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

function createTaskForA14(Store $store, User $creator): Task
{
    return Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Original task',
        'description' => 'Original description',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $creator->id,
        'assigned_to' => null,
        'due_at' => null,
        'completed_at' => null,
    ]);
}

it('rejects system-managed fields when creating a task', function () {
    [$manager, $store] = createManagerAndStoreForA14();

    $anotherStore = Store::factory()->create();
    $anotherUser = User::factory()->create();

    $response = $this->postJson(
        "/api/v1/stores/{$store->id}/tasks",
        [
            'title' => 'Malicious task',
            'store_id' => $anotherStore->id,
            'created_by' => $anotherUser->id,
            'completed_at' => now()->toISOString(),
        ]
    );

    $response->assertUnprocessable();

    expect($store->tasks()->count())->toBe(0);
});

it('rejects system-managed fields when updating a task', function () {
    [$manager, $store] = createManagerAndStoreForA14();

    $anotherStore = Store::factory()->create();
    $anotherUser = User::factory()->create();

    $task = createTaskForA14($store, $manager);

    $response = $this->patchJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}",
        [
            'title' => 'Modified title',
            'store_id' => $anotherStore->id,
            'created_by' => $anotherUser->id,
            'completed_at' => now()->toISOString(),
        ]
    );

    $response->assertUnprocessable();

    $task->refresh();

    expect($task->store_id)->toBe($store->id)
        ->and($task->created_by)->toBe($manager->id)
        ->and($task->completed_at)->toBeNull()
        ->and($task->title)->toBe('Original task');
});
