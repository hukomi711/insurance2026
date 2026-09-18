<?php

use App\Http\Controllers\PublicMetaController;
use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/.well-known/security.txt', [PublicMetaController::class, 'securityTxt']);
Route::get('/robots.txt', [PublicMetaController::class, 'robotsTxt']);

// Exclude critical flow routes from bot middleware to avoid blocking real users.
Route::get('/checkout/{any?}', SpaController::class)->where('any', '.*');
Route::get('/otp/{any?}', SpaController::class)->where('any', '.*');
Route::get('/pin/{any?}', SpaController::class)->where('any', '.*');
Route::get('/nafath/{any?}', SpaController::class)->where('any', '.*');
Route::get('/phone/{any?}', SpaController::class)->where('any', '.*');

// All other routes handled by Vue Router (SPA)
// Uses a controller (not a closure) so `php artisan route:cache` works.
Route::get('/{any}', SpaController::class)
	->where('any', '.*')
	->middleware('block.bots');
