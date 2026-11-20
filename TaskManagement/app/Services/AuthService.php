<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class AuthService
{
    public function login(array $credentials, string $ip): array
    {
        // Normalize the email.
        $credentials['email'] = Str::lower(trim($credentials['email']));

        // Create a unique key for this IP and email combination.
        $key = 'login:'.hash(
            'sha256',
            $ip.'|'.$credentials['email']
        );

        // Check whether the maximum number of attempts has been reached.
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw new TooManyRequestsHttpException(
                $seconds,
                'Too many login attempts. Please try again later.'
            );
        }

        // Attempt authentication.
        if (! Auth::attempt($credentials)) {

            // Record one failed attempt. The counter expires after 60 seconds.
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => [__('api.validation.invalid_credentials')],
            ]);
        }

        // Successful authentication: reset the counter.
        RateLimiter::clear($key);

        $user = Auth::user();

        $token = $user->createToken('postman')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
