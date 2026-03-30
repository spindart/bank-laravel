<?php

use App\Exceptions\Finance\FinanceException;
use App\Http\Middleware\RequestLoggingMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequestLoggingMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validacao.',
                'data' => null,
                'errors' => $exception->errors(),
            ], 422);
        });

        $exceptions->render(function (FinanceException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
                'data' => null,
                'errors' => null,
            ], $exception->statusCode());
        });

        $exceptions->render(function (UnauthorizedHttpException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Nao autenticado.',
                'data' => null,
                'errors' => null,
            ], 401);
        });

        $exceptions->render(function (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor.',
                'data' => null,
                'errors' => null,
            ], 500);
        });
    })->create();
