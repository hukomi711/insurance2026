<?php

namespace Tests\Unit;

use App\Http\Middleware\PerformanceMonitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class PerformanceMonitorTest extends TestCase
{
    public function test_logging_failure_does_not_replace_successful_response(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('daily')
            ->andThrow(new RuntimeException('log file is not writable'));

        $request = Request::create('/api/test', 'GET');
        $response = app(PerformanceMonitor::class)->handle(
            $request,
            static fn () => response(str_repeat('x', 512_001), 200),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(512_001, strlen($response->getContent()));
    }
}
