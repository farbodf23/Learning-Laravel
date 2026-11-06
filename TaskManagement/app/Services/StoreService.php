<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;

class StoreService
{
    public function listMembers(Store $store): Collection
    {
        return $store->memberships()
            ->with('user:id,name,email')
            ->get();
    }
}