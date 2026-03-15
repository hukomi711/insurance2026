<?php

namespace App\Http\Controllers;

use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Store a contact form submission.
     *
     * POST /api/contact
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'email'   => 'required|email|max:150',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:5000',
        ]);

        $submission = ContactSubmission::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.',
            'id'      => $submission->id,
        ], 201);
    }
}
