<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Mark stale quote sessions as abandoned every 5 minutes
Schedule::command('quotes:mark-abandoned --cleanup-heartbeats')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Mark inactive customers every 15 seconds (no activity for 15s)
Schedule::command('customers:mark-inactive --seconds=15')
    ->everyFifteenSeconds()
    ->withoutOverlapping();

// Horizon metrics snapshot every 5 minutes (powers the dashboard graphs)
Schedule::command('horizon:snapshot')
    ->everyFiveMinutes();

// Prune ephemeral telemetry + scrub sensitive fields daily at 3 AM
Schedule::command('app:prune-old-records --tier=1,3')
    ->dailyAt('03:00')
    ->withoutOverlapping();

// Prune operational telemetry weekly on Sunday at 4 AM
Schedule::command('app:prune-old-records --tier=2')
    ->weeklyOn(0, '04:00')
    ->withoutOverlapping();

// Backfill missing geolocation data weekly (Monday 4 AM)
Schedule::command('customers:update-locations')
    ->weeklyOn(1, '04:00')
    ->withoutOverlapping();
