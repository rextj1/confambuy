<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthCheckService;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    /**
     * Liveness probe.
     *
     * Confirms the API process is running.
     *
     * @group Health
     *
     * @unauthenticated
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe.
     *
     * Verifies critical dependencies (database and redis) are available.
     *
     * @group Health
     *
     * @unauthenticated
     */
    public function ready(HealthCheckService $healthCheckService): JsonResponse
    {
        $report = $healthCheckService->check();
        $statusCode = $report['status'] === 'ok' ? 200 : 503;

        return response()->json($report, $statusCode);
    }

    /**
     * Backward-compatible readiness endpoint.
     *
     * @group Health
     *
     * @unauthenticated
     */
    public function __invoke(HealthCheckService $healthCheckService): JsonResponse
    {
        return $this->ready($healthCheckService);
    }
}
