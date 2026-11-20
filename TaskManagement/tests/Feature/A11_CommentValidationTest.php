
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

function createManagerAndTaskForA11(): array
{
    $manager = User::factory()->create();
    $store = Store::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'created_by' => $manager->id,
        'assigned_to' => null,
        'title' => 'Prepare monthly report',
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'due_at' => null,
        'completed_at' => null,
    ]);

    return [$manager, $store, $task];
}

it('rejects empty comments', function () {
    [$manager, $store, $task] = createManagerAndTaskForA11();

    Sanctum::actingAs($manager);

    $response = $this->postJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/comments",
        ['body' => '']
    );

    $response->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonStructure(['error' => ['fields' => ['body']]]);

    expect(TaskComment::where('task_id', $task->id)->count())
        ->toBe(0);
});

it('prevents staff from commenting on tasks they cannot see', function () {
    $manager = User::factory()->create();
    $staffA = User::factory()->create();
    $staffB = User::factory()->create();

    $store = Store::factory()->create();

    foreach ([$manager, $staffA, $staffB] as $user) {
        StoreMembership::create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'role' => $user->id === $manager->id ? 'manager' : 'staff',
        ]);
    }

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'created_by' => $manager->id,
        'assigned_to' => $staffB->id,
        'title' => 'Prepare monthly report',
        'description' => null,
        'status' => 'todo',
        'priority' => 'medium',
        'due_at' => null,
        'completed_at' => null,
    ]);

    Sanctum::actingAs($staffA);

    $response = $this->postJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/comments",
        ['body' => 'This task is not assigned to me.']
    );

    $response->assertNotFound();

    expect(TaskComment::where('task_id', $task->id)->count())
        ->toBe(0);
});

it('paginates comments for an authorized user', function () {
    [$manager, $store, $task] = createManagerAndTaskForA11();

    Sanctum::actingAs($manager);

    // Create 25 existing comments.
    for ($i = 1; $i <= 25; $i++) {
        TaskComment::forceCreate([
            'store_id' => $store->id,
            'task_id' => $task->id,
            'user_id' => $manager->id,
            'body' => "Comment number {$i}",
            'created_at' => now()->subMinutes(25 - $i),
        ]);
    }

    $response = $this->getJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/comments"
    );

    $response->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.total', 25)
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonPath('meta.page', 1);
});
