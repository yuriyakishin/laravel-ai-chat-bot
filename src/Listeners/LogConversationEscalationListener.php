<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Listeners;

use Illuminate\Support\Facades\Log;
use Yu\AiChatBot\Events\ConversationEscalatedToHumanEvent;

class LogConversationEscalationListener
{
    /**
     * @param ConversationEscalatedToHumanEvent $event
     * @return void
     */
    public function handle(ConversationEscalatedToHumanEvent $event): void
    {
        Log::channel('ai-chat')->info('conversation escalated to human', [
            'conversation_uuid' => $event->conversation->uuid,
            'reason' => $event->arguments['reason'] ?? '',
        ]);
    }
}
