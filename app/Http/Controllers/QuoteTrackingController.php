<?php

namespace App\Http\Controllers;

use App\Models\QuoteSession;
use App\Models\QuoteStepLog;
use App\Models\QuoteHeartbeat;
use App\Services\DeviceDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteTrackingController extends Controller
{
    /**
     * Start a new quote session — returns UUID
     * POST /api/quote/start
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'insurance_type' => 'nullable|string|max:50',
            'referrer_url' => 'nullable|string|max:500',
            'utm_source' => 'nullable|string|max:100',
            'utm_medium' => 'nullable|string|max:100',
            'utm_campaign' => 'nullable|string|max:100',
        ]);

        $session = QuoteSession::create([
            'customer_ip' => $request->ip(),
            'current_step' => 'motorapp',
            'step_number' => 1,
            'insurance_type' => $validated['insurance_type'] ?? null,
            'status' => 'active',
            'completion_percentage' => 10,
            'device_type' => DeviceDetectionService::detectType($request),
            'device_browser' => DeviceDetectionService::detectBrowser($request),
            'user_agent' => $request->userAgent(),
            'started_at' => now(),
            'last_heartbeat_at' => now(),
            'referrer_url' => $validated['referrer_url'] ?? null,
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
        ]);

        // Create initial step log
        QuoteStepLog::create([
            'quote_session_id' => $session->id,
            'step_name' => 'motorapp',
            'step_number' => 1,
            'entered_at' => now(),
        ]);

        return response()->json([
            'uuid' => $session->uuid,
            'session_id' => $session->id,
        ], 201);
    }

    /**
     * Record a step transition
     * POST /api/quote/{uuid}/step
     */
    public function step(Request $request, string $uuid): JsonResponse
    {
        $session = QuoteSession::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'step' => 'required|string|max:50',
            'step_number' => 'required|integer|min:1|max:10',
            'form_data' => 'nullable|array',
            'exit_reason' => 'nullable|string|in:next,back,abandon,refresh',
        ]);

        // Close the current step log
        /** @var \App\Models\QuoteStepLog|null $currentLog */
        $currentLog = $session->stepLogs()
            ->whereNull('exited_at')
            ->latest()
            ->first();

        if ($currentLog) {
            $currentLog->close(
                $validated['exit_reason'] ?? 'next',
                $validated['form_data'] ?? null
            );
        }

        // Compute completion percentage
        $completionMap = QuoteSession::stepCompletionMap();
        $completion = $completionMap[$validated['step']] ?? $session->completion_percentage;

        // Update session
        $updateData = [
            'current_step' => $validated['step'],
            'step_number' => $validated['step_number'],
            'completion_percentage' => $completion,
            'last_heartbeat_at' => now(),
        ];

        // Store form data in the appropriate JSON column
        if (!empty($validated['form_data'])) {
            $dataColumn = $this->getDataColumn($session->current_step);
            if ($dataColumn) {
                $updateData[$dataColumn] = $validated['form_data'];
            }
        }

        $session->update($updateData);

        // Create new step log
        QuoteStepLog::create([
            'quote_session_id' => $session->id,
            'step_name' => $validated['step'],
            'step_number' => $validated['step_number'],
            'entered_at' => now(),
        ]);

        return response()->json([
            'status' => 'ok',
            'completion' => $completion,
        ]);
    }

    /**
     * Heartbeat ping — keeps session alive
     * POST /api/quote/{uuid}/heartbeat
     */
    public function heartbeat(Request $request, string $uuid): JsonResponse
    {
        $session = QuoteSession::where('uuid', $uuid)
            ->where('status', 'active')
            ->firstOrFail();

        $validated = $request->validate([
            'step' => 'required|string|max:50',
            'tab_visible' => 'boolean',
        ]);

        // Record heartbeat
        QuoteHeartbeat::create([
            'quote_session_id' => $session->id,
            'current_step' => $validated['step'],
            'customer_ip' => $request->ip(),
            'tab_visible' => $validated['tab_visible'] ?? true,
            'pinged_at' => now(),
        ]);

        // Update session timestamp
        $session->update([
            'last_heartbeat_at' => now(),
            'current_step' => $validated['step'],
        ]);

        // Increment interaction count on current step log
        $currentLog = $session->stepLogs()
            ->whereNull('exited_at')
            ->latest()
            ->first();

        if ($currentLog) {
            $currentLog->increment('interaction_count');
        }

        return response()->json(['status' => 'alive']);
    }

    /**
     * Mark session as completed
     * POST /api/quote/{uuid}/complete
     */
    public function complete(Request $request, string $uuid): JsonResponse
    {
        $session = QuoteSession::where('uuid', $uuid)->firstOrFail();

        // Close last step log
        /** @var \App\Models\QuoteStepLog|null $currentLog */
        $currentLog = $session->stepLogs()
            ->whereNull('exited_at')
            ->latest()
            ->first();

        if ($currentLog) {
            $currentLog->close('completed', $request->input('form_data'));
        }

        $session->markCompleted();

        return response()->json(['status' => 'completed']);
    }

    /**
     * Resume an existing session (e.g., page refresh)
     * GET /api/quote/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $session = QuoteSession::where('uuid', $uuid)
            ->with('stepLogs')
            ->firstOrFail();

        return response()->json($session->toMonitorFormat());
    }

    // ─── Private Helpers ────────────────────────────────────

    private function getDataColumn(string $step): ?string
    {
        return match ($step) {
            'motorapp' => null,
            'vehicle' => 'vehicle_data',
            'compare' => 'comparison_data',
            'details' => 'personal_data',
            default => null,
        };
    }
}
