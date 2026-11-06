<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StoreMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validate the submitted credentials
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // 2. Verify the credentials
        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // 3. Retrieve the authenticated user
        $user = Auth::user();

        // 4. Generate a Sanctum token
        $token = $user->createToken('postman')->plainTextToken;

        // 5. Return the token
        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ],
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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function myStores(Request $request)
    {
        $memberships = StoreMembership::query()
            ->where('user_id', $request->user()->id)
            ->with('store:id,name')
            ->get();

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
