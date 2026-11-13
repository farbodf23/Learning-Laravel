<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\ChangeTaskStatusRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\TaskIndexRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Store;
use App\Models\Task;
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

    public function showTask(
        Store $store,
        Task $task
    ) {
        Gate::authorize('view', [$task, $store]);

        $task->load([
            'creator:id,name',
            'assignee:id,name',
        ]);

        return response()->json([
            'data' => $task,
        ]);
    }

    public function updateTask(
        UpdateTaskRequest $request,
        Store $store,
        Task $task,
        TaskService $taskService
    ) {
        Gate::authorize('update', [$task, $store]);

        $updatedTask = $taskService->updateTask(
            $store,
            $task,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'data' => $updatedTask,
        ]);
    }

    public function changeTaskStatus(
        ChangeTaskStatusRequest $request,
        Store $store,
        Task $task,
        TaskService $taskService
    ) {
        Gate::authorize('changeStatus', [$task, $store]);

        $updatedTask = $taskService->changeTaskStatus(
            $store,
            $task,
            $request->user(),
            $request->validated()['status']
        );

        return response()->json([
            'data' => $updatedTask,
        ]);
    }

    public function assignTask(
        AssignTaskRequest $request,
        Store $store,
        Task $task,
        TaskService $taskService
    ) {
        Gate::authorize('assign', [$task, $store]);

        $updatedTask = $taskService->assignTask(
            $store,
            $task,
            $request->user(),
            $request->validated()['assigned_to']
        );

        return response()->json([
            'data' => $updatedTask,
        ]);
    }

    public function storeComment(
        StoreCommentRequest $request,
        Store $store,
        Task $task,
        TaskService $taskService
    ) {
        Gate::authorize('createComment', [$task, $store]);

        $comment = $taskService->addComment(
            $store,
            $task,
            $request->user(),
            $request->validated('body')
        );

        return response()->json([
            'data' => $comment,
        ], 201);
    }

    public function taskComments(
        Store $store,
        Task $task,
        TaskService $taskService
    ) {
        Gate::authorize('viewComments', [$task, $store]);

        $comments = $taskService->listComments($task);

        return response()->json($comments);
    }

    public function taskEvents(
        Store $store,
        Task $task,
        TaskService $taskService
    ) {
        Gate::authorize('view', [$task, $store]);

        $events = $taskService->listEvents($task);

        return response()->json($events);
    }

    public function taskSummary(
        Store $store,
        TaskService $taskService
    ) {
        Gate::authorize('viewTaskSummary', $store);

        $summary = $taskService->taskSummary($store);

        return response()->json([
            'data' => $summary,
        ]);
    }
}
