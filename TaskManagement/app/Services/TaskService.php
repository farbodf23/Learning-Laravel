<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function listTasks(
        Store $store,
        User $user,
        array $filters = []
    ): LengthAwarePaginator {

        $membership = $store->membershipFor($user);

        // Start with tasks belonging to this store.
        $query = $store->tasks();

        // Staff can only see tasks assigned to themselves.
        if ($membership->role === 'staff') {
            $query->where('assigned_to', $user->id);
        }

        // Filter by status.
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by priority.
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Filter by assignee.
        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Filter overdue tasks.
        if (array_key_exists('overdue', $filters)) {
            $isOverdue = filter_var(
                $filters['overdue'],
                FILTER_VALIDATE_BOOLEAN
            );

            if ($isOverdue) {
                $query->whereNotNull('due_at')
                    ->where('due_at', '<', now())
                    ->whereIn('status', ['todo', 'in_progress']);
            } else {
                $query->where(function ($query) {
                    $query->whereNull('due_at')
                        ->orWhere('due_at', '>=', now())
                        ->orWhereNotIn('status', ['todo', 'in_progress']);
                });
            }
        }

        // Search title and description.
        if (isset($filters['q'])) {
            $search = $filters['q'];

            $query->where(function ($query) use ($search) {
                $query->where('title', 'ILIKE', "%{$search}%")
                    ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Load related users and paginate.
        return $query
            ->with([
                'creator:id,name',
                'assignee:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20);
    }

    public function createTask(
        Store $store,
        User $user,
        array $data
    ): Task {
        //Transaction so that an eventless task is not created.
        return DB::transaction(function () use ($store, $user, $data) {

            // 1. Validate the assignee's store membership.
            if (! empty($data['assigned_to'])) {
                $isMember = $store->memberships()
                    ->where('user_id', $data['assigned_to'])
                    ->exists();

                if (! $isMember) {
                    throw ValidationException::withMessages([
                        'assigned_to' => [
                            'The assigned user is not a member of this store.',
                        ],
                    ]);
                }
            }

            // 2. Create the task.
            $task = $store->tasks()->create([
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'todo',
                'created_by' => $user->id,
                'assigned_to' => $data['assigned_to'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'completed_at' => null,
            ]);

            // 3. Record the initial event.
            $task->events()->create([
                'store_id' => $store->id,
                'actor_id' => $user->id,
                'event_type' => 'created',
                'changes' => [
                    'title' => $task->title,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'assigned_to' => $task->assigned_to,
                    'due_at' => $task->due_at,
                ],
            ]);

            // 4. Load relationships needed in the response.
            return $task->load([
                'creator:id,name',
                'assignee:id,name',
            ]);
        });
    }
}
