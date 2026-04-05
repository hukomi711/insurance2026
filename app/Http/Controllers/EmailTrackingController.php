<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class EmailTrackingController extends Controller
{
    /**
     * Track email open via 1x1 transparent pixel.
     * GET /api/email/open/{id}
     */
    public function trackOpen(int $id): Response
    {
        EmailLog::where('id', $id)
            ->where('status', EmailLog::STATUS_SENT)
            ->update([
                'opened_at'  => DB::raw('COALESCE(opened_at, NOW())'),
                'open_count' => DB::raw('open_count + 1'),
            ]);

        // Return 1x1 transparent GIF
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, [
            'Content-Type'  => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
        ]);
    }

    /**
     * Track email click and redirect to site.
     * GET /api/email/click/{id}
     */
    public function trackClick(int $id)
    {
        EmailLog::where('id', $id)
            ->where('status', EmailLog::STATUS_SENT)
            ->update([
                'clicked_at'  => DB::raw('COALESCE(clicked_at, NOW())'),
                'click_count' => DB::raw('click_count + 1'),
            ]);

        return redirect()->to(url('/') . '?utm_source=email&utm_medium=recovery&utm_campaign=abandoned');
    }
}
