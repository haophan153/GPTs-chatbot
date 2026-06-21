<?php

namespace App\Http\Middleware;

use App\Models\ApiCallLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiCalls
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $bookingCode = null;
        $path = $request->path();
        if (preg_match('/bookings\/([A-Za-z0-9\-]+)/', $path, $matches)) {
            $bookingCode = $matches[1];
        }

        try {
            ApiCallLog::create([
                'booking_code' => $bookingCode,
                'endpoint' => '/' . $path,
                'method' => $request->method(),
                'request_body' => @json_decode($request->getContent(), true) ?: $request->all(),
                'response_status' => $response->getStatusCode(),
                'response_body' => @json_decode($response->getContent(), true),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'called_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Don't let logging failures break the API
        }

        return $response;
    }
}
