<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SpaController extends Controller
{
    /**
     * Serve the SPA shell for all frontend routes.
     *
     * This must be a controller (not a closure) so that
     * `php artisan route:cache` can serialize the route.
     */
    public function __invoke(Request $request): View
    {
        $path = trim((string) $request->path(), '/');
        $blocked = [
            'sitemap.xml',
            'route-map',
            'route-map.json',
            'routes.map',
        ];

        if (in_array($path, $blocked, true)) {
            throw new NotFoundHttpException();
        }

        return view('app');
    }
}
