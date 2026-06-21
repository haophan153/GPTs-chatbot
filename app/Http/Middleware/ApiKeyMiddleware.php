<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $validKey = config('services.api.key');

        if (empty($validKey)) {
            return response()->json([
                'error' => 'API key not configured',
            ], 500);
        }

        if (empty($apiKey) || !hash_equals($validKey, $apiKey)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key',
            ], 401);
        }

        return $next($request);
    }
}
