<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
it('rejects unauthenticated requests', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
});

it('revokes the access token after logout', function () {
    $user = User::factory()->create();

    $token = $user->createToken('test-token')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertOk();

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    // Verify that the token was actually deleted from the database.
    $this->assertDatabaseMissing('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'test-token',
    ]);

    // Clear cached authentication guards before the next request.
    Auth::forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});