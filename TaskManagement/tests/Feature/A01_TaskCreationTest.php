
<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\TaskEvent;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

/**
 * Create a manager, a store, and their membership.
 */
function createManagerAndStoreForA01(): array
{
    $manager = User::factory()->create();

    $store = Store::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    Sanctum::actingAs($manager);

    return [
        'manager' => $manager,
        'store' => $store,
    ];
}

it('allows a manager to create a valid task', function () {
    $context = createManagerAndStoreForA01();

    $manager = $context['manager'];
    $store = $context['store'];

    $payload = [
        'title' => 'Prepare monthly report',
        'description' => 'Collect and review monthly sales data.',
        'priority' => 'high',
    ];

    $response = $this->postJson(
        "/api/v1/stores/{$store->id}/tasks",
        $payload
    );

    $response->assertCreated()
        ->assertJsonPath('data.title', 'Prepare monthly report')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.status', 'todo')
        ->assertJsonPath('data.created_by', $manager->id)
        ->assertJsonPath('data.assigned_to', null)
        ->assertJsonPath('data.completed_at', null);

    $this->assertDatabaseHas('tasks', [
        'store_id' => $store->id,
        'created_by' => $manager->id,
        'title' => 'Prepare monthly report',
        'priority' => 'high',
        'status' => 'todo',
    ]);

    $this->assertDatabaseHas('task_events', [
        'store_id' => $store->id,
        'actor_id' => $manager->id,
        'event_type' => 'created',
    ]);

    expect(Task::count())->toBe(1);
    expect(TaskEvent::count())->toBe(1);
});

it('rejects incomplete task data without saving anything', function () {
    $context = createManagerAndStoreForA01();

    $payload = [
        'description' => 'Collect and review monthly sales data.',
        'priority' => 'high',
    ];

    $response = $this->postJson(
        "/api/v1/stores/{$context['store']->id}/tasks",
        $payload
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title']);

    expect(Task::count())->toBe(0);
    expect(TaskEvent::count())->toBe(0);
});
