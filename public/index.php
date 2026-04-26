<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Support both VPS (Docker) and shared hosting layouts:
//   VPS:            public/ sits inside the project root
//   Shared hosting: public_html/ sits beside the laravel/ app folder
$sharedHostingBase = __DIR__.'/../laravel';
$basePath = is_dir($sharedHostingBase) ? $sharedHostingBase : __DIR__.'/..';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $basePath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $basePath.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
