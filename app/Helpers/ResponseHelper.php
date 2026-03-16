<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class ResponseHelper
{
    public static function jsonResponse($statusCode, $message, $data = null, $traceId = null)
    {
        // Generate a 4-digit random number if traceId is not provided
        $traceId = $traceId ?? [str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT)];

        // Build response array
        $response = [
            'statusCode' => $statusCode,
            'message'    => $message,
            'data'       => $data,
        ];

        // Only include traceId if not a 2xx status code
        if ($statusCode < 200 || $statusCode >= 300) {
            $response['traceId'] = $traceId;

            // Log the response
            Log::info([
                'statusCode' => $statusCode,
                'message'    => $message,
                'data'       => $data,
                'traceId'    => $traceId,
                'timestamp'  => now()->toDateTimeString(),
            ]);
        }

        return response()->json($response, $statusCode);
    }
}
