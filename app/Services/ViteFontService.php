<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ViteFontService
{
    /**
     * Get preload font URLs from Vite manifest.
     * Cached permanently — invalidated only on deploy via `php artisan vite:clear-fonts`.
     *
     * @return array{noto-kufi: string|null, roboto: string|null}
     */
    public function getFontUrls(): array
    {
        try {
            return Cache::rememberForever('vite_font_preloads', function () {
                return $this->resolveFontsFromManifest();
            });
        } catch (\Throwable $e) {
            Log::warning('[ViteFontService] Cache unavailable, resolving fonts directly', [
                'error' => $e->getMessage(),
            ]);
            return $this->resolveFontsFromManifest();
        }
    }

    /**
     * Clear the cached font URLs (call after each deploy / build).
     */
    public static function clearCache(): void
    {
        Cache::forget('vite_font_preloads');
    }

    /**
     * Parse the Vite manifest and extract font paths.
     * Uses early-break to avoid iterating the entire manifest.
     */
    private function resolveFontsFromManifest(): array
    {
        // Vite 5+ uses .vite/manifest.json, older versions use manifest.json directly
        $manifestPath = public_path('build/.vite/manifest.json');
        if (!file_exists($manifestPath)) {
            $manifestPath = public_path('build/manifest.json');
        }

        if (!file_exists($manifestPath)) {
            Log::warning('[ViteFontService] Manifest not found — font preloads disabled', [
                'path' => $manifestPath,
            ]);
            return ['noto-kufi' => null, 'roboto' => null];
        }

        try {
            $manifest = json_decode(
                file_get_contents($manifestPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            Log::error('[ViteFontService] Failed to parse Vite manifest', [
                'error' => $e->getMessage(),
            ]);
            return ['noto-kufi' => null, 'roboto' => null];
        }

        $notoKufi = null;
        $roboto = null;

        foreach ($manifest as $key => $entry) {
            // Stop once both fonts are found
            if ($notoKufi && $roboto) {
                break;
            }

            $file = $entry['file'] ?? null;
            if (!$file || !str_ends_with($key, '.woff2')) {
                continue;
            }

            // Prioritize the Arabic unicode-range subset (most critical for RTL site)
            if (!$notoKufi && str_contains($key, 'noto-kufi-arabic') && str_contains($key, 'arabic-400')) {
                $notoKufi = $file;
            } elseif (!$roboto && str_contains($key, 'roboto') && str_contains($key, 'latin-400')) {
                $roboto = $file;
            }
        }

        return [
            'noto-kufi' => $notoKufi,
            'roboto'    => $roboto,
        ];
    }
}
