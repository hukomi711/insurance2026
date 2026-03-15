<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

// All routes handled by Vue Router (SPA)
// Uses a controller (not a closure) so `php artisan route:cache` works.
Route::get('/{any}', SpaController::class)->where('any', '.*');
