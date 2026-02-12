<?php

namespace Tests\Feature;

use App\Services\Health\HealthCheckService;
use Mockery;
use Tests\TestCase;

class HealthCheckApiTest extends TestCase
{
    public function test_health_live_endpoint_returns_ok_response(): void
    {
        $this->getJson('/api/health/live')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'timestamp']);
    }

    public function test_health_ready_endpoint_returns_ok_response(): void
    {
        $mock = Mockery::mock(HealthCheckService::class);
        $mock->shouldReceive('check')->once()->andReturn([
            'status' => 'ok',
            'checks' => [
                'database' => ['status' => 'up'],
                'redis' => ['status' => 'up'],
            ],
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->app->instance(HealthCheckService::class, $mock);

        $this->getJson('/api/health/ready')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'up')
            ->assertJsonPath('checks.redis.status', 'up');
    }

    public function test_health_ready_endpoint_returns_service_unavailable_when_degraded(): void
    {
        $mock = Mockery::mock(HealthCheckService::class);
        $mock->shouldReceive('check')->once()->andReturn([
            'status' => 'degraded',
            'checks' => [
                'database' => ['status' => 'up'],
                'redis' => ['status' => 'down'],
            ],
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->app->instance(HealthCheckService::class, $mock);

        $this->getJson('/api/health/ready')
            ->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.database.status', 'up')
            ->assertJsonPath('checks.redis.status', 'down');
    }

    public function test_health_endpoint_alias_maps_to_ready_probe(): void
    {
        $mock = Mockery::mock(HealthCheckService::class);
        $mock->shouldReceive('check')->once()->andReturn([
            'status' => 'ok',
            'checks' => [
                'database' => ['status' => 'up'],
                'redis' => ['status' => 'up'],
            ],
            'timestamp' => now()->toIso8601String(),
        ]);

        $this->app->instance(HealthCheckService::class, $mock);

        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
}
