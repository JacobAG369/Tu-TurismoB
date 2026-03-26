<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\HandleCors;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(HandleCors::class);
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        $middleware->alias([
            'is.admin' => \App\Http\Middleware\IsAdmin::class,
            'vigenere.session' => \App\Http\Middleware\ValidateVigenereSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e): bool {
            return $request->expectsJson() || $request->is('api/*');
        });

        // Handle InvalidArgumentException (from ImageService validation)
        $exceptions->render(function (\InvalidArgumentException $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'errors'  => [
                    'imagen' => [$e->getMessage()],
                ],
            ], 422);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], $e->status);
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            if ($status < 500 && $status !== 401 && $status !== 403 && $status !== 404) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor.',
                'details' => config('app.debug')
                    ? [
                        'exception' => $e::class,
                        'error' => $e->getMessage(),
                    ]
                    : [],
            ], $status);
        });
    })->create();
