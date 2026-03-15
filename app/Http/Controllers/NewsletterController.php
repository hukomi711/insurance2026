<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    /**
     * الاشتراك في النشرة البريدية
     *
     * POST /api/newsletter
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'  => 'required|email|max:255',
            'source' => 'nullable|string|in:blog,footer,popup',
        ]);

        // Check if already subscribed
        $existing = NewsletterSubscriber::where('email', $validated['email'])->first();

        if ($existing) {
            if ($existing->status === 'unsubscribed') {
                $existing->update([
                    'status'           => 'active',
                    'unsubscribed_at'  => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'تم إعادة تفعيل اشتراكك بنجاح',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'أنت مشترك بالفعل في النشرة البريدية',
            ]);
        }

        NewsletterSubscriber::create([
            'email'  => $validated['email'],
            'source' => $validated['source'] ?? 'blog',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم الاشتراك في النشرة البريدية بنجاح',
        ], 201);
    }
}
