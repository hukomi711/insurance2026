<?php

namespace App\Console\Commands;

use App\Services\ViteFontService;
use Illuminate\Console\Command;

class ClearViteFontCache extends Command
{
    protected $signature = 'vite:clear-fonts';

    protected $description = 'Clear cached Vite font preload URLs and re-warm the cache';

    public function handle(): int
    {
        ViteFontService::clearCache();
        $this->components->info('Vite font cache cleared.');

        // Re-warm immediately so the first request is instant
        $fonts = app(ViteFontService::class)->getFontUrls();

        $noto  = $fonts['noto-kufi'] ?? '(not found)';
        $robot = $fonts['roboto']    ?? '(not found)';

        $this->components->twoColumnDetail('Noto Kufi Arabic', $noto);
        $this->components->twoColumnDetail('Roboto', $robot);

        return self::SUCCESS;
    }
}
