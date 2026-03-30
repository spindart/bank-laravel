<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RequestLoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $requestId = (string) str()->uuid();

        try {
            /** @var Response $response */
            $response = $next($request);
            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);

            Log::info('http.request', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => $durationMs,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
            ]);

            $response->headers->set('X-Request-Id', $requestId);
            $response->headers->set('X-Response-Time-Ms', (string) $durationMs);

            return $response;
        } catch (Throwable $exception) {
            $durationMs = round((microtime(true) - $startedAt) * 1000, 2);

            Log::error('http.request_failed', [
                'request_id' => $requestId,
                'method' => $request->method(),
                'path' => $request->path(),
                'duration_ms' => $durationMs,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}

