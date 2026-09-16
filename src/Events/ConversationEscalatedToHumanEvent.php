<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Events;

use Yu\AiChatBot\Models\Conversation;

class ConversationEscalatedToHumanEvent
{
    /**
     * @param Conversation $conversation
     * @param array $arguments
     */
    public function __construct(
        public readonly Conversation $conversation,
        public readonly array $arguments
    ) {
    }
}
