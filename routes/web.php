<?php

use App\Http\Controllers\PublicMetaController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// Env-driven public meta files (must precede the SPA catch-all).
Route::get('/robots.txt', [PublicMetaController::class, 'robots']);
Route::get('/sitemap.xml', [PublicMetaController::class, 'sitemap']);
Route::get('/.well-known/security.txt', [PublicMetaController::class, 'securityTxt']);

// All other routes handled by Vue Router (SPA)
// Uses a controller (not a closure) so `php artisan route:cache` works.
Route::get('/{any}', SpaController::class)->where('any', '.*');
