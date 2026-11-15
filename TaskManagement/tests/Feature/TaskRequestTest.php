<?php

namespace Tests\Feature;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TaskRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/test/tasks', function (StoreTaskRequest $request) {
            return response()->json($request->validated());
        });

        Route::patch('/test/tasks/{task}', function (UpdateTaskRequest $request) {
            return response()->json($request->validated());
        });
    }

    public function test_create_rejects_unknown_attributes(): void
    {
        $this->postJson('/test/tasks', [
            'title' => 'A valid title',
            'created_by' => 999,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['created_by']);
    }

    public function test_update_rejects_unknown_attributes(): void
    {
        $this->patchJson('/test/tasks/1', [
            'title' => 'A valid title',
            'status' => 'done',
            'created_by' => 999,
            'assigned_to' => 999,
            'completed_at' => '2026-11-15T10:00:00Z',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
                'created_by',
                'assigned_to',
                'completed_at',
            ]);
    }
}
