<?php

namespace App\Http\Controllers;

use App\Models\ApiCallLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ApiCallLog::orderBy('called_at', 'desc');

        if ($request->booking_code) {
            $query->where('booking_code', $request->booking_code);
        }

        $logs = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function show(string $bookingCode): JsonResponse
    {
        $logs = ApiCallLog::where('booking_code', $bookingCode)
            ->orderBy('called_at', 'asc')
            ->get();

        if ($logs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No logs found for this booking.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
