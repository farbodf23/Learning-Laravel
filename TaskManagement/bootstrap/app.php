<?php

use App\Http\Middleware\SetApiLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prependToGroup(
            'api',
            SetApiLocale::class
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (
            Response $response,
            Throwable $exception,
            Request $request
        ) {
            if (! $request->is('api/*') || $response->getStatusCode() < 400) {
                return $response;
            }

            $status = $response->getStatusCode();
            $payload = match ($status) {
                400 => ['code' => 'BAD_REQUEST', 'message' => __('api.errors.bad_request', [], 'fa')],
                401 => ['code' => 'UNAUTHENTICATED', 'message' => __('api.errors.unauthenticated', [], 'fa')],
                403 => ['code' => 'FORBIDDEN', 'message' => __('api.errors.forbidden', [], 'fa')],
                404 => ['code' => 'NOT_FOUND', 'message' => __('api.errors.not_found', [], 'fa')],
                405 => ['code' => 'METHOD_NOT_ALLOWED', 'message' => __('api.errors.method_not_allowed', [], 'fa')],
                409 => ['code' => 'CONFLICT', 'message' => __('api.errors.conflict', [], 'fa')],
                419 => ['code' => 'CSRF_TOKEN_MISMATCH', 'message' => __('api.errors.csrf_token_mismatch', [], 'fa')],
                422 => ['code' => 'VALIDATION_ERROR', 'message' => __('api.errors.validation', [], 'fa')],
                429 => ['code' => 'TOO_MANY_REQUESTS', 'message' => __('api.errors.too_many_requests', [], 'fa')],
                default => [
                    'code' => $status >= 500 ? 'INTERNAL_SERVER_ERROR' : 'HTTP_ERROR',
                    'message' => __('api.errors.generic', [], 'fa'),
                ],
            };

            if ($status === 422) {
                $data = $response instanceof JsonResponse
                    ? $response->getData(true)
                    : [];
                $payload['fields'] = $data['errors'] ?? [];
            }

            $headers = $response->headers->all();
            unset($headers['content-type'], $headers['content-length']);

            return response()->json(['error' => $payload], $status, $headers);
        });
    })->create();
