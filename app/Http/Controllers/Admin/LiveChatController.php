<?php

namespace App\Http\Controllers\Admin;

use App\Events\NewLivechatMessage;
use App\Http\Controllers\Controller;
use App\Models\LivechatConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LiveChatController extends Controller
{
    /**
     * List all active conversations with unread counts.
     *
     * GET /api/admin/livechat/conversations
     */
    public function index(): JsonResponse
    {
        try {
            $conversations = LivechatConversation::active()
                ->orderByDesc('last_message_at')
                ->take(50)
                ->get();

            $totalUnread = $conversations->sum('unread_count');

            return response()->json([
                'success' => true,
                'conversations' => $conversations,
                'unreadCount' => $totalUnread,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Gracefully degrade if table doesn't exist or DB error
            return response()->json([
                'success' => true,
                'conversations' => [],
                'unreadCount' => 0,
            ]);
        }
    }

    /**
     * Get messages for a specific conversation.
     *
     * GET /api/admin/livechat/conversations/{sessionId}
     */
    public function show(string $sessionId): JsonResponse
    {
        $conversation = LivechatConversation::where('session_id', $sessionId)->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get();

        // Mark unread visitor messages as read
        $conversation->messages()
            ->where('sender', 'visitor')
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $conversation->update(['unread_count' => 0]);

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a reply from the admin to a conversation.
     *
     * POST /api/admin/livechat/conversations/{sessionId}/reply
     */
    public function reply(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $conversation = LivechatConversation::where('session_id', $sessionId)->firstOrFail();

        /** @var \App\Models\LivechatMessage $message */
        $message = $conversation->messages()->create([
            'sender' => 'admin',
            'admin_id' => Auth::id(),
            'message' => $request->input('message'),
            'is_read' => true,
        ]);

        // Update conversation metadata
        $conversation->update([
            'last_message' => $request->input('message'),
            'last_message_at' => now(),
        ]);

        // Broadcast to the visitor's channel
        broadcast(new NewLivechatMessage($conversation, $message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Receive a message from a visitor (public endpoint).
     *
     * POST /api/livechat/send
     */
    public function visitorSend(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:100',
            'message' => 'required|string|max:2000',
            'name' => 'nullable|string|max:100',
        ]);

        $sessionId = $request->input('session_id');

        $conversation = LivechatConversation::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'visitor_ip' => $request->ip(),
                'visitor_name' => $request->input('name', 'زائر'),
                'visitor_page' => $request->header('Referer'),
                'status' => 'active',
            ]
        );

        // Update visitor name if provided and currently default
        if ($request->filled('name') && $conversation->visitor_name === 'زائر') {
            $conversation->update(['visitor_name' => $request->input('name')]);
        }

        /** @var \App\Models\LivechatMessage $message */
        $message = $conversation->messages()->create([
            'sender' => 'visitor',
            'message' => $request->input('message'),
        ]);

        // Increment unread count + update last message
        $conversation->increment('unread_count');
        $conversation->update([
            'last_message' => $request->input('message'),
            'last_message_at' => now(),
        ]);

        // Broadcast to admin dashboard
        broadcast(new NewLivechatMessage($conversation, $message));

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Get conversation messages for a visitor (public endpoint).
     *
     * GET /api/livechat/{sessionId}/messages
     */
    public function visitorMessages(string $sessionId): JsonResponse
    {
        $conversation = LivechatConversation::where('session_id', $sessionId)->first();

        if (! $conversation) {
            return response()->json(['success' => true, 'messages' => []]);
        }

        $messages = $conversation->messages()
            ->orderBy('created_at')
            ->get(['id', 'sender', 'message', 'created_at']);

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }
}
