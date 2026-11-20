<?php

use App\Models\Store;
use App\Models\StoreMembership;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('returns Persian validation errors in the standard API format', function () {
    $store = Store::factory()->create();
    $manager = User::factory()->create();

    StoreMembership::create([
        'store_id' => $store->id,
        'user_id' => $manager->id,
        'role' => 'manager',
    ]);

    Sanctum::actingAs($manager);

    $this->postJson("/api/v1/stores/{$store->id}/tasks", [
        'title' => 'ab',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'VALIDATION_ERROR')
        ->assertJsonPath('error.message', 'اطلاعات ورودی معتبر نیست.')
        ->assertJsonPath(
            'error.fields.title.0',
            'فیلد عنوان باید حداقل 3 نویسه داشته باشد.'
        );
});

it('returns the standard error format for authentication and missing resources', function () {
    $this->getJson('/api/v1/me')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'UNAUTHENTICATED');

    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/stores/999999/tasks')
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});

it('localizes invalid login credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'incorrect-password',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('error.fields.email.0', 'اطلاعات ورود نامعتبر است.');
});

it('returns the standard error format for forbidden and conflicting requests', function () {
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

    Sanctum::actingAs($staff);

    $this->postJson("/api/v1/stores/{$store->id}/tasks", [
        'title' => 'Valid title',
    ])
        ->assertForbidden()
        ->assertJsonPath('error.code', 'FORBIDDEN');

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Valid title',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => null,
    ]);

    Sanctum::actingAs($manager);

    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/status",
        ['status' => 'done']
    )
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'CONFLICT');
});

it('returns the standard error format when login attempts are rate limited', function () {
    $email = 'rate-limit@example.com';
    User::factory()->create([
        'email' => $email,
        'password' => Hash::make('correct-password'),
    ]);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => 'wrong-password',
    ])
        ->assertStatus(429)
        ->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
});

it('hides tasks from staff who are not assigned to them', function () {
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

    $task = Task::forceCreate([
        'store_id' => $store->id,
        'title' => 'Unassigned task',
        'status' => 'todo',
        'priority' => 'medium',
        'created_by' => $manager->id,
        'assigned_to' => null,
    ]);

    Sanctum::actingAs($staff);

    $this->patchJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}",
        ['title' => 'Updated title']
    )
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');

    $this->putJson(
        "/api/v1/stores/{$store->id}/tasks/{$task->id}/assignee",
        ['assigned_to' => $manager->id]
    )
        ->assertNotFound()
        ->assertJsonPath('error.code', 'NOT_FOUND');
});
