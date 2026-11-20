
<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->travelTo(
        Carbon::parse('2026-06-15 12:00:00')
    );
});

it('returns identical errors for an unknown email and an incorrect password', function () {
    // Create an account so we can test an incorrect password.
    User::factory()->create([
        'email' => 'existing@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    // Attempt login with an email that does not exist.
    $unknownEmailResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'unknown@example.com',
        'password' => 'some-password',
    ]);

    // Attempt login with an existing email but incorrect password.
    $wrongPasswordResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'existing@example.com',
        'password' => 'wrong-password',
    ]);

    $unknownEmailResponse->assertUnprocessable();
    $wrongPasswordResponse->assertUnprocessable();

    // Neither response should reveal whether the account exists.
    expect($unknownEmailResponse->json())
        ->toEqual($wrongPasswordResponse->json());
});

it('limits failed login attempts and allows login again after the limit expires', function () {
    $user = User::factory()->create([
        'email' => 'ratelimit@example.com',
        'password' => Hash::make('correct-password'),
    ]);

    // Five failed attempts should be allowed.
    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    // The sixth attempt should be rate-limited.
    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);

    // Advance beyond the one-minute decay period.
    $this->travel(61)->seconds();

    // A valid login should now be allowed.
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'token',
            ],
        ]);
});
