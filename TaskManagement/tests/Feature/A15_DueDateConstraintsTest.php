
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->travelTo(
        Carbon::parse('2026-06-15 12:00:00')
    );
});

function createManagerAndStoreForA15(): array
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

function createTaskForA15(Store $store, User $manager, array $attributes = []): Task
{
    return Task::forceCreate(array_merge([
        'store_id' => $store->id,
        'title' => 'Original task title',
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => null,
        'due_at' => now()->addDays(2),
        'completed_at' => null,
    ], $attributes));
}

it('rejects creating a task with a past due date', function () {
    [, $store] = createManagerAndStoreForA15();

    $response = $this->postJson(
        "/api/v1/stores/{$store->id}/tasks",
        [
            'title' => 'New task',
            'due_at' => now()->subDay()->toISOString(),
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['fields' => ['due_at']]]);

    expect(Task::count())->toBe(0);
});

it('rejects updating a task due date to a past date', function () {
    [$manager, $store] = createManagerAndStoreForA15();

    $task = createTaskForA15($store, $manager);

    $originalDueDate = $task->due_at;

    $response = $this->patchJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}",
        [
            'due_at' => now()->subDay()->toISOString(),
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['fields' => ['due_at']]]);

    $task->refresh();

    expect($task->due_at->equalTo($originalDueDate))->toBeTrue();
});

it('allows editing the title of an already overdue task', function () {
    [$manager, $store] = createManagerAndStoreForA15();

    $pastDueDate = now()->subDays(3);

    $task = createTaskForA15($store, $manager, [
        'due_at' => $pastDueDate,
    ]);

    $response = $this->patchJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}",
        [
            'title' => 'Updated overdue task title',
        ]
    );

    $response->assertOk();

    $task->refresh();

    expect($task->title)->toBe('Updated overdue task title')
        ->and($task->due_at->equalTo($pastDueDate))->toBeTrue();
});
