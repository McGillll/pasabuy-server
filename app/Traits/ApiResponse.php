<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Success', int $code = Response::HTTP_OK)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message, int $code, $data = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}
