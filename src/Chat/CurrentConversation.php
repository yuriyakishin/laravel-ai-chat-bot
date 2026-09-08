<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Chat;

class CurrentConversation
{
    private ?string $uuid = null;

    /**
     * @param string $uuid
     * @return void
     */
    public function set(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function get(): string
    {
        return $this->uuid;
    }
}
