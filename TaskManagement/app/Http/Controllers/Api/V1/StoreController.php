<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskIndexRequest;
use App\Models\Store;
use App\Services\StoreService;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreController extends Controller
{
    public function members(
        Request $request,
        Store $store,
        StoreService $storeService
    ) {
        Gate::authorize('viewMembers', $store);

        $memberships = $storeService->listMembers($store);

        return response()->json([
            'data' => $memberships->map(function ($membership) {
                return [
                    'user_id' => $membership->user->id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                    'role' => $membership->role,
                ];
            }),
        ]);
    }

    public function tasks(
        TaskIndexRequest $request,
        Store $store,
        TaskService $taskService
    ) {
        Gate::authorize('viewTasks', $store);

        $tasks = $taskService->listTasks(
            $store,
            $request->user(),
            $request->validated()
        );

        return response()->json($tasks);
    }

    public function storeTask(
        StoreTaskRequest $request,
        Store $store,
        TaskService $taskService
    ) {
        Gate::authorize('createTask', $store);

        $task = $taskService->createTask(
            $store,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'data' => $task,
        ], 201);
    }
}
