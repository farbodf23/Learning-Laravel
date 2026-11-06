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
        int $perPage = 20
    ): LengthAwarePaginator {

        $membership = $store->membershipFor($user);

        $query = $store->tasks();

        if ($membership->role === 'staff') {
            $query->where('assigned_to', $user->id);
        }

        return $query
            ->with([
                'creator:id,name',
                'assignee:id,name',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}