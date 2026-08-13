<?php

namespace Tests\Unit;

use Tests\TestCase;

class DatabaseQueueFallbackTest extends TestCase
{
    public function test_database_and_queue_metadata_use_the_same_mysql_fallback(): void
    {
        $sources = file_get_contents(config_path('database.php'))
            .file_get_contents(config_path('queue.php'));

        preg_match_all(
            "/env\\('DB_CONNECTION',\\s*'([^']+)'\\)/",
            $sources,
            $matches,
        );

        $this->assertCount(3, $matches[1]);
        $this->assertSame(['mysql', 'mysql', 'mysql'], $matches[1]);
    }

    public function test_loaded_queue_metadata_connections_match_the_application_database(): void
    {
        $this->assertSame(config('database.default'), config('queue.batching.database'));
        $this->assertSame(config('database.default'), config('queue.failed.database'));
    }
}
