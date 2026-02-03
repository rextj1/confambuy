<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    public static function resource(JsonResource $resource, int $status = 200): JsonResponse
    {
        return $resource->response()->setStatusCode($status);
    }

    public static function collection(ResourceCollection $collection, int $status = 200): JsonResponse
    {
        return $collection->response()->setStatusCode($status);
    }

    public static function message(string $message, int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'meta' => $meta,
        ], $status);
    }
}
