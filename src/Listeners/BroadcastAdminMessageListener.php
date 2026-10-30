<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Listeners;

use Illuminate\Support\Facades\Log;
use Throwable;
use Yu\AiChatBot\Broadcasting\AdminMessageBroadcast;
use Yu\AiChatBot\Events\AdminMessageSentEvent;

class BroadcastAdminMessageListener
{
    /**
     * @param AdminMessageSentEvent $event
     * @return void
     */
    public function handle(AdminMessageSentEvent $event): void
    {
        try {
            AdminMessageBroadcast::dispatch($event->message);
        } catch (Throwable $e) {
            Log::channel('ai-chat')->warning('failed to broadcast admin message', [
                'message_id' => $event->message->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
