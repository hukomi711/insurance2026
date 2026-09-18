<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

/**
 * Pricing Constants Controller
 *
 * Provides pricing constants/factors to frontend for:
 * 1. Loading current server-side factors
 * 2. Verifying version match between server and client
 * 3. Enabling dynamic price updates without redeploy
 *
 * GET /api/pricing/constants
 */
class PricingConstantsController extends Controller
{
    /**
     * Get all pricing constants/factors.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $config = config('pricing');

        return response()->json([
            'version' => $config['version'],
            'lastUpdated' => $config['last_updated'],
            'vat_rate' => $config['vat_rate'],
            'signature_ttl' => $config['signature_ttl'],
            'constants' => [
                'fixed_company_prices' => $config['fixed_company_prices'] ?? [],
                'deductible_increase' => $config['deductible_increase'] ?? [],
                'addons_prices' => $config['addons_prices'] ?? [],
                'supported_company_ids' => array_values(array_map('intval', array_keys($config['fixed_company_prices'] ?? []))),
                'supported_deductibles' => array_values(array_map('intval', array_keys($config['deductible_increase'] ?? []))),
            ],
            'timestamp' => now()->timestamp,
            'hash' => md5(json_encode($config)),
        ]);
    }

    /**
     * Check if client version matches server version.
     *
     * POST /api/pricing/constants/verify
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function verify(\Illuminate\Http\Request $request): JsonResponse
    {
        $clientVersion = $request->input('version');
        $clientHash = $request->input('hash');

        $config = config('pricing');
        $serverVersion = $config['version'];
        $serverHash = md5(json_encode($config));

        $matches = $clientVersion === $serverVersion && $clientHash === $serverHash;

        return response()->json([
            'match' => $matches,
            'clientVersion' => $clientVersion,
            'serverVersion' => $serverVersion,
            'message' => $matches
                ? 'Pricing constants match'
                : 'Pricing constants mismatch — client should reload',
        ]);
    }
}
