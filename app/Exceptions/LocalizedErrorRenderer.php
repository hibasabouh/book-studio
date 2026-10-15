<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LocalizedErrorRenderer
{
    /**
     * Translation key for each status whose framework-default JSON message is replaced.
     */
    private const MESSAGES = [
        401 => 'messages.unauthenticated',
        403 => 'messages.forbidden',
        404 => 'messages.not_found',
        405 => 'messages.method_not_allowed',
        413 => 'messages.payload_too_large',
        419 => 'messages.session_expired',
        429 => 'messages.too_many_requests',
        500 => 'messages.server_error',
        503 => 'messages.service_unavailable',
    ];

    /**
     * Render a translated JSON error, or null to let Laravel render it as usual.
     */
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->expectsJson() || $this->hasAppMessage($e)) {
            return null;
        }

        $status = $this->statusFor($e);

        if ($status === null || ! isset(self::MESSAGES[$status])) {
            return null;
        }

        $headers = $e instanceof HttpExceptionInterface ? $e->getHeaders() : [];

        return response()->json(['message' => __(self::MESSAGES[$status])], $status, $headers);
    }

    private function statusFor(Throwable $e): ?int
    {
        return match (true) {
            $e instanceof AuthenticationException => 401,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            $e instanceof ValidationException, $e instanceof HttpResponseException => null,
            // Never leak SQL to the client, even with APP_DEBUG on; the full error is still logged.
            $e instanceof QueryException => 500,
            // Keep the debug page/trace for other unexpected errors while developing.
            (bool) config('app.debug') => null,
            default => 500,
        };
    }

    /**
     * abort(4xx, __('...')) throws a plain HttpException carrying an already-translated message.
     */
    public function hasAppMessage(Throwable $e): bool
    {
        return $e::class === HttpException::class
            && $e->getPrevious() === null
            && $e->getStatusCode() < 500
            && $e->getMessage() !== '';
    }
}
