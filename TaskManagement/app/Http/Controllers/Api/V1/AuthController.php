<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\StoreService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request, AuthService $authService)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $authService->login($credentials);

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $result['user']->id,
                    'name' => $result['user']->name,
                    'email' => $result['user']->email,
                ],
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(Request $request, AuthService $authService)
    {
        $authService->logout($request->user());

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function myStores(Request $request, StoreService $storeService)
    {
        $memberships = $storeService->listUserStores($request->user());

        return response()->json([
            'data' => $memberships->map(function ($membership) {
                return [
                    'store_id' => $membership->store->id,
                    'store_name' => $membership->store->name,
                    'role' => $membership->role,
                ];
            }),
        ]);
    }
}
