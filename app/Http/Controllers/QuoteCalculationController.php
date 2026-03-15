<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalculateQuoteRequest;
use App\Services\QuoteCalculationService;
use Illuminate\Http\JsonResponse;

class QuoteCalculationController extends Controller
{
    public function __construct(
        private QuoteCalculationService $service
    ) {}

    /**
     * Calculate pricing for a batch of insurance plans.
     * POST /api/quotes/calculate
     */
    public function calculate(CalculateQuoteRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $quotes = $this->service->calculateForPlans(
            $validated['plans'],
            $validated['vehicle'],
            $validated['driver'],
            $validated['policy'],
        );

        return response()->json([
            'quotes' => $quotes,
        ]);
    }
}
