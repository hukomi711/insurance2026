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
                'base_premiums' => $config['base_premiums'],
                'price_limits' => $config['price_limits'],
                'vehicle_age_factors' => $config['vehicle_age_factors'],
                'vehicle_value_factors' => $config['vehicle_value_factors'],
                'purpose_factors' => $config['purpose_factors'],
                'modification_factor' => $config['modification_factor'],
                'trailer_factor' => $config['trailer_factor'],
                'driver_age_factors' => $config['driver_age_factors'],
                'accident_factors' => $config['accident_factors'],
                'violation_factors' => $config['violation_factors'],
                'education_factors' => $config['education_factors'],
                'foreign_license_factor' => $config['foreign_license_factor'],
                'health_condition_factor' => $config['health_condition_factor'],
                'additional_driver_factor' => $config['additional_driver_factor'],
                'city_factors' => $config['city_factors'],
                'parking_factors' => $config['parking_factors'],
                'mileage_factors' => $config['mileage_factors'],
                'deductible_factors' => $config['deductible_factors'],
                'repair_method_factors' => $config['repair_method_factors'],
                'coverage_limit_factors' => $config['coverage_limit_factors'],
                'company_pricing_factors' => $config['company_pricing_factors'],
                'ncd_factors' => $config['ncd_factors'],
                'manufacturer_factors' => $config['manufacturer_factors'] ?? [],
                'experience_factors' => $config['experience_factors'],
                'transmission_factors' => $config['transmission_factors'],
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
