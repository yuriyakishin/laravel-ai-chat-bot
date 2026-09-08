<?php

namespace Yu\AiChatBot\Contracts;

interface ChatServiceInterface
{
    /**
     * Sends a message on behalf of the owner and returns the reply.
     *
     * @param mixed $owner
     * @param string $conversationUuid
     * @param string $message
     * @return ChatResultInterface
     */
    public function send(mixed $owner, string $conversationUuid, string $message): ChatResultInterface;
}
