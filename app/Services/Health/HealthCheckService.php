<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthCheckService
{
    /**
     * @return array{
     *     status: string,
     *     checks: array{
     *         database: array{status: string},
     *         redis: array{status: string}
     *     },
     *     timestamp: string
     * }
     */
    public function check(): array
    {
        $database = $this->checkDatabase();
        $redis = $this->checkRedis();
        $isHealthy = $database['status'] === 'up' && $redis['status'] === 'up';

        return [
            'status' => $isHealthy ? 'ok' : 'degraded',
            'checks' => [
                'database' => $database,
                'redis' => $redis,
            ],
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{status: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->select('select 1');

            return ['status' => 'up'];
        } catch (Throwable) {
            return ['status' => 'down'];
        }
    }

    /**
     * @return array{status: string}
     */
    private function checkRedis(): array
    {
        try {
            $connectionName = (string) config('api_health.redis_connection', 'cache');
            $pingResponse = Redis::connection($connectionName)->ping();

            if ($pingResponse === true || $pingResponse === 1) {
                return ['status' => 'up'];
            }

            if (is_string($pingResponse) && strtoupper(ltrim($pingResponse, '+')) === 'PONG') {
                return ['status' => 'up'];
            }

            return ['status' => 'down'];
        } catch (Throwable) {
            return ['status' => 'down'];
        }
    }
}
