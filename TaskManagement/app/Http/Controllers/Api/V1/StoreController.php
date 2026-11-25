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
use Illuminate\Support\Facades\Gate;

class StoreController extends Controller
{
    public function members(

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

        return response()->json([
            'data' => $tasks->items(),

            'meta' => [
                'page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ],
        ]);
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

        return response()
            ->json([
                'data' => $task,
            ], 201)
            ->header(
                'Location',
                url("/api/v1/stores/{$store->id}/tasks/{$task->id}")
            );
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

        return response()->json([
            'data' => $comments->items(),

            'meta' => [
                'page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function taskEvents(
        Store $store,
        Task $task,
        TaskService $taskService
    ) {
        Gate::authorize('view', [$task, $store]);

        $events = $taskService->listEvents($task);

        return response()->json([
            'data' => $events->items(),

            'meta' => [
                'page' => $events->currentPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
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

    public function exportTasks(
        TaskIndexRequest $request,
        Store $store,
        TaskService $taskService
    ) {
        Gate::authorize('viewTasks', $store);

        $tasks = $taskService->exportTasks(
            $store,
            $request->user(),
            $request->validated()
        );

        return response()->streamDownload(
            function () use ($tasks) {
                $handle = fopen('php://output', 'w');

                // UTF-8 BOM helps Excel recognize UTF-8 CSV files.
                fwrite($handle, "\xEF\xBB\xBF");

                $headers = [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'creator_name',
                    'assignee_name',
                    'due_at',
                    'completed_at',
                    'created_at',
                ];

                fputcsv($handle, $headers, ',', '"', '');

                foreach ($tasks as $task) {
                    fputcsv($handle, [
                        $task->id,
                        $this->safeCsvCell($task->title),
                        $this->safeCsvCell($task->description),
                        $task->status,
                        $task->priority,
                        $this->safeCsvCell($task->creator_name),
                        $this->safeCsvCell($task->assignee_name),
                        $task->due_at,
                        $task->completed_at,
                        $task->created_at,
                    ], ',', '"', '');
                }

                fclose($handle);
            },
            'kasbify-tasks-'.now()->format('Y-m-d').'.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    private function safeCsvCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        // Prevent spreadsheet applications from interpreting
        // untrusted text as a formula.
        if (preg_match('/^[\x00-\x20]*[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
