<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormatApiResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$request->expectsJson() && !$request->is('api/*')) {
            return $response;
        }

        if (!$response instanceof JsonResponse) {
            return $response;
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return $response;
        }

        $payload = $response->getData(true);
        if (!is_array($payload)) {
            $payload = [
                'message' => 'OK',
                'data' => $payload,
            ];
        } elseif (!array_key_exists('message', $payload)) {
            $payload['message'] = 'OK';
        }

        $response->setData($payload);

        return $response;
    }
}
