<?php

namespace App\Events;

use App\Models\LivechatConversation;
use App\Models\LivechatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class NewLivechatMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * Queue name — handled by Horizon's broadcasts-supervisor.
     */
    public string $broadcastQueue = 'broadcasts';

    public string $sessionId;

    public string $visitorIp;

    public string $visitorName;

    public string $sender;

    public string $message;

    public int $messageId;

    public int $conversationId;

    public function __construct(LivechatConversation $conversation, LivechatMessage $chatMessage)
    {
        $this->sessionId = $conversation->session_id;
        $this->visitorIp = $conversation->visitor_ip ?? '';
        $this->visitorName = $conversation->visitor_name;
        $this->sender = $chatMessage->sender;
        $this->message = $chatMessage->message;
        $this->messageId = $chatMessage->id;
        $this->conversationId = $conversation->id;
    }

    /**
     * Broadcast to the private admin channel. Visitors use the existing
     * authenticated polling endpoint for conversation updates.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-livechat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewLivechatMessage';
    }

    /**
     * Control the data broadcast — avoid leaking visitor IP.
     */
    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'sender' => $this->sender,
            'message' => $this->message,
            'message_id' => $this->messageId,
            'conversation_id' => $this->conversationId,
            'visitor_name' => $this->visitorName,
        ];
    }
}
