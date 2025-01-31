<?php

namespace Project\Infrastructure\Laravel\API\Utils;

use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    protected function success(array $data = null, string $message = 'Success'): JsonResponse
    {
        return \response()->json([
            'status' => 200,
            'message' => $message,
            'dataset' => $data,
        ]);
    }

    protected function error(int $statusCode, string $message = 'Error', array $data = null): JsonResponse
    {
        return \response()->json([
            'status' => $statusCode,
            'errorMessage' => $message,
            'dataset' => $data,
        ], $statusCode);
    }
}