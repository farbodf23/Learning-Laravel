<?php

namespace App\Http\Controllers\Api\V1;
use App\Services\TaskService;
use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Services\StoreService;
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
    Request $request,
    Store $store,
    TaskService $taskService
) {
    Gate::authorize('viewTasks', $store);

    $tasks = $taskService->listTasks(
        $store,
        $request->user()
    );

    return response()->json($tasks);
}
}
