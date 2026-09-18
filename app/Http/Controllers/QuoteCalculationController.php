<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateQuoteRequest;
use App\Services\SimplePricingService;
use Illuminate\Http\JsonResponse;

class QuoteCalculationController extends Controller
{
    public function __construct(
        private SimplePricingService $service
    ) {}

    /**
     * Calculate pricing for a batch of insurance plans.
     * POST /api/quotes/calculate
     *
     * Returns quotes with digital signatures for security verification
     * on payment submission.
     */
    public function calculate(CalculateQuoteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Calculate with signatures for client-side protection
        $quotes = $this->service->calculateForPlansWithSignature(
            $validated['plans'],
            $validated['vehicle'],
            $validated['driver'],
            $validated['policy'],
            logCalculation: true
        );

        return response()->json([
            'quotes' => $quotes,
            'pricingVersion' => config('pricing.version'),
            'timestamp' => now()->timestamp,
        ]);
    }
}
