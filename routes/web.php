<?php

use App\Http\Controllers\PublicMetaController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/.well-known/security.txt', [PublicMetaController::class, 'securityTxt']);
Route::get('/robots.txt', [PublicMetaController::class, 'robotsTxt']);
Route::get('/sitemap.xml', [PublicMetaController::class, 'sitemapXml']);

// All other routes handled by Vue Router (SPA)
// Uses a controller (not a closure) so `php artisan route:cache` works.
Route::get('/{any}', SpaController::class)->where('any', '.*');
