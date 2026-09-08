<?php

declare(strict_types=1);

namespace Yu\AiChatBot\DTO;

use Yu\AiChatBot\Contracts\ChatResultInterface;

final readonly class ChatResult implements ChatResultInterface
{
    /**
     * @param string $reply
     * @param string $conversationUuid
     * @param int $promptTokens
     * @param int $completionTokens
     */
    public function __construct(
        private string $reply,
        private string $conversationUuid,
        private int $promptTokens,
        private int $completionTokens,
    ) {
    }

    /**
     * @return string
     */
    public function reply(): string
    {
        return $this->reply;
    }

    /**
     * @return string
     */
    public function conversationUuid(): string
    {
        return $this->conversationUuid;
    }

    /**
     * @return int
     */
    public function promptTokens(): int
    {
        return $this->promptTokens;
    }

    /**
     * @return int
     */
    public function completionTokens(): int
    {
        return $this->completionTokens;
    }
}
