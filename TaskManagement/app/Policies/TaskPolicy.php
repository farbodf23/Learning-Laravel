<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    public function view(
        User $user,
        Task $task,
        Store $store
    ): Response {
        // Make sure the task belongs to the requested store.
        if ($task->store_id !== $store->id) {
            return Response::denyAsNotFound();
        }

        // Check whether the user belongs to this store.
        $membership = $store->membershipFor($user);

        if ($membership === null) {
            return Response::denyAsNotFound();
        }

        // Staff can only view their assigned tasks.
        if (
            $membership->role === 'staff' &&
            $task->assigned_to !== $user->id
        ) {
            return Response::deny(
                'This task is not assigned to you.'
            );
        }

        return Response::allow();
    }

    public function update(
        User $user,
        Task $task,
        Store $store
    ): Response {
        // Ensure the task belongs to the requested store.
        if ($task->store_id !== $store->id) {
            return Response::denyAsNotFound();
        }

        // Check store membership.
        $membership = $store->membershipFor($user);

        if ($membership === null) {
            return Response::denyAsNotFound();
        }

        // Managers can update every task in their store.
        if ($membership->role === 'manager') {
            return Response::allow();
        }

        // Staff can only update tasks assigned to them.
        if (
            $membership->role === 'staff' &&
            $task->assigned_to === $user->id
        ) {
            return Response::allow();
        }

        return Response::deny(
            'You are not authorized to update this task.'
        );
    }
}
