<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Events;

use Yu\AiChatBot\Contracts\LlmResponseInterface;

class LlmResponseReceived
{
    /**
     * @param LlmResponseInterface $response
     * @param string $conversationUuid
     */
    public function __construct(
        public readonly LlmResponseInterface $response,
        public readonly string $conversationUuid
    ) {
    }
}
