
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

function createManagerAndStoreForA13(): array
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

function createTaskForA13(
    Store $store,
    User $manager,
    array $attributes = []
): Task {
    return Task::forceCreate(array_merge([
        'store_id' => $store->id,
        'created_by' => $manager->id,
        'assigned_to' => null,
        'title' => 'Test task',
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'due_at' => null,
        'completed_at' => null,
    ], $attributes));
}

beforeEach(function () {
    // Freeze time so overdue calculations are predictable.
    $this->travelTo(
        Carbon::parse('2026-06-15 12:00:00')
    );
});

it('returns only overdue tasks when overdue filter is true', function () {
    [$manager, $store] = createManagerAndStoreForA13();

    $overdueTodo = createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);

    $overdueInProgress = createTaskForA13($store, $manager, [
        'status' => 'in_progress',
        'due_at' => now()->subHours(2),
    ]);

    // Done tasks are never overdue.
    createTaskForA13($store, $manager, [
        'status' => 'done',
        'due_at' => now()->subDay(),
        'completed_at' => now()->subHours(3),
    ]);

    // Cancelled tasks are never overdue.
    createTaskForA13($store, $manager, [
        'status' => 'cancelled',
        'due_at' => now()->subDay(),
    ]);

    // Future and missing due dates are not overdue.
    createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => now()->addDay(),
    ]);

    createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => null,
    ]);

    Sanctum::actingAs($manager);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks?overdue=true"
    );

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);

    $returnedIds = collect($response->json('data'))
        ->pluck('id')
        ->all();

    expect($returnedIds)->toContain($overdueTodo->id)
        ->toContain($overdueInProgress->id);
});

it('excludes overdue tasks when overdue filter is false', function () {
    [$manager, $store] = createManagerAndStoreForA13();

    createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);

    createTaskForA13($store, $manager, [
        'status' => 'in_progress',
        'due_at' => now()->subDay(),
    ]);

    $doneTask = createTaskForA13($store, $manager, [
        'status' => 'done',
        'due_at' => now()->subDay(),
        'completed_at' => now()->subHours(2),
    ]);

    $cancelledTask = createTaskForA13($store, $manager, [
        'status' => 'cancelled',
        'due_at' => now()->subDay(),
    ]);

    $futureTask = createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => now()->addDay(),
    ]);

    $noDueDateTask = createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => null,
    ]);

    Sanctum::actingAs($manager);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks?overdue=false"
    );

    $response->assertOk()
        ->assertJsonCount(4, 'data')
        ->assertJsonPath('meta.total', 4);

    $returnedIds = collect($response->json('data'))
        ->pluck('id')
        ->all();

    expect($returnedIds)->toContain($doneTask->id)
        ->toContain($cancelledTask->id)
        ->toContain($futureTask->id)
        ->toContain($noDueDateTask->id);
});

it('calculates task summary metrics correctly', function () {
    [$manager, $store] = createManagerAndStoreForA13();

    // Three todo tasks: one overdue, one future, one without a due date.
    createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => now()->subDay(),
    ]);

    createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => now()->addDay(),
    ]);

    createTaskForA13($store, $manager, [
        'status' => 'todo',
        'due_at' => null,
    ]);

    // One overdue in-progress task.
    createTaskForA13($store, $manager, [
        'status' => 'in_progress',
        'due_at' => now()->subHours(2),
    ]);

    // A completed task with a past due date.
    createTaskForA13($store, $manager, [
        'status' => 'done',
        'due_at' => now()->subDay(),
        'completed_at' => now()->subHours(1),
    ]);

    // A cancelled task with a past due date.
    createTaskForA13($store, $manager, [
        'status' => 'cancelled',
        'due_at' => now()->subDay(),
    ]);

    Sanctum::actingAs($manager);

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/task-summary"
    );

    $response->assertOk()
        ->assertJsonPath('data.total', 6)
        ->assertJsonPath('data.by_status.todo', 3)
        ->assertJsonPath('data.by_status.in_progress', 1)
        ->assertJsonPath('data.by_status.done', 1)
        ->assertJsonPath('data.by_status.cancelled', 1)
        ->assertJsonPath('data.overdue', 2);
});
