<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class StoreService
{
    public function listMembers(Store $store): Collection
    {
        return $store->memberships()
            ->with('user:id,name,email')
            ->get();
    }

    public function listUserStores(User $user): Collection
    {
        return $user->memberships()
            ->with('store:id,name')
            ->get();
    }
}
