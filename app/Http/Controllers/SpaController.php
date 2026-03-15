<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SpaController extends Controller
{
    /**
     * Serve the SPA shell for all frontend routes.
     *
     * This must be a controller (not a closure) so that
     * `php artisan route:cache` can serialize the route.
     */
    public function __invoke(): View
    {
        return view('app');
    }
}
