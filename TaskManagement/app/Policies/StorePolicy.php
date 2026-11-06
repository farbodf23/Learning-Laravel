<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StorePolicy
{
    public function viewMembers(User $user, Store $store): Response
    {
        $membership = $store->membershipFor($user);

        if ($membership === null) {
            return Response::denyAsNotFound();
        }

        if ($membership->role !== 'manager') {
            return Response::deny('Only store managers can view members.');
        }

        return Response::allow();
    }

    public function viewTasks(User $user, Store $store): Response
    {
        $membership = $store->membershipFor($user);

        if ($membership === null) {
            return Response::denyAsNotFound();
        }

        return Response::allow();
    }
}
