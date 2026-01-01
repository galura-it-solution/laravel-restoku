<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ValidationException $e, Request $request) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        });

        $this->renderable(function (HttpResponseException $e, Request $request) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null;
            }

            $response = $e->getResponse();
            $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 500;
            $content = method_exists($response, 'getContent') ? $response->getContent() : null;
            $decoded = is_string($content) ? json_decode($content, true) : null;

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return response()->json($decoded, $status);
            }

            return response()->json([
                'message' => $content ?: 'Error.',
            ], $status);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            if (!$request->expectsJson() && !$request->is('api/*')) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof HttpResponseException) {
                return null;
            }

            $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            $message = $e->getMessage() ?: 'Terjadi kesalahan.';
            if ($status >= 500 && !config('app.debug')) {
                $message = 'Terjadi kesalahan pada server.';
            }

            return response()->json([
                'message' => $message,
            ], $status);
        });
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return Route::has('login')
            ? redirect()->guest(route('login'))
            : abort(401);
    }
}
