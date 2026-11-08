<?php

namespace App\Services;

use App\Models\Store;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

}