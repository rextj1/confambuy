<?php

namespace Tests\Unit;

use App\Services\Health\HealthCheckService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Mockery;
use Tests\TestCase;

class HealthCheckServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('api_health.redis_connection', 'cache');
    }

    public function test_it_reports_ok_when_database_and_redis_are_up(): void
    {
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([[1]]);

        $redisConnection = Mockery::mock();
        $redisConnection->shouldReceive('ping')->once()->andReturn('PONG');
        Redis::shouldReceive('connection')->once()->with('cache')->andReturn($redisConnection);

        $report = app(HealthCheckService::class)->check();

        $this->assertSame('ok', $report['status']);
        $this->assertSame('up', $report['checks']['database']['status']);
        $this->assertSame('up', $report['checks']['redis']['status']);
    }

    public function test_it_reports_degraded_when_database_is_down(): void
    {
        DB::shouldReceive('connection')->once()->andThrow(new \RuntimeException('db down'));

        $redisConnection = Mockery::mock();
        $redisConnection->shouldReceive('ping')->once()->andReturn('PONG');
        Redis::shouldReceive('connection')->once()->with('cache')->andReturn($redisConnection);

        $report = app(HealthCheckService::class)->check();

        $this->assertSame('degraded', $report['status']);
        $this->assertSame('down', $report['checks']['database']['status']);
        $this->assertSame('up', $report['checks']['redis']['status']);
    }

    public function test_it_reports_degraded_when_redis_is_down(): void
    {
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([[1]]);

        Redis::shouldReceive('connection')->once()->with('cache')->andThrow(new \RuntimeException('redis down'));

        $report = app(HealthCheckService::class)->check();

        $this->assertSame('degraded', $report['status']);
        $this->assertSame('up', $report['checks']['database']['status']);
        $this->assertSame('down', $report['checks']['redis']['status']);
    }
}
