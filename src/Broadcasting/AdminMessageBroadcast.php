<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Broadcasting;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Yu\AiChatBot\Models\Message;

class AdminMessageBroadcast implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * @param Message $message
     */
    public function __construct(private readonly Message $message)
    {
    }

    /**
     * @return PrivateChannel
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('ai-chat.conversation.' . $this->message->conversation->uuid);
    }

    /**
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'ai-chat.message-sent';
    }

    /**
     * @return array
     */
    public function broadcastWith(): array
    {
        return $this->message->only(['id', 'role', 'content', 'created_at']);
    }
}
