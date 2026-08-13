<?php

namespace Tests\Unit;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ConfigCacheCompatibilityTest extends TestCase
{
    public function test_runtime_php_does_not_read_environment_outside_config_files(): void
    {
        $roots = ['app', 'bootstrap', 'database', 'resources/views', 'routes'];
        $violations = [];

        foreach ($roots as $root) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(base_path($root), RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if ($contents !== false && preg_match('/\benv\s*\(/', $contents) === 1) {
                    $violations[] = str_replace('\\', '/', $file->getPathname());
                }
            }
        }

        $this->assertSame(
            [],
            $violations,
            "Runtime env() calls found outside config files:\n".implode("\n", $violations)
        );
    }
}
