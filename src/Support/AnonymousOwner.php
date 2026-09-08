<?php

declare (strict_types=1);

namespace Yu\AiChatBot\Support;

final class AnonymousOwner
{
    /**
     * @param string $visitorId
     */
    public function __construct(private readonly string $visitorId)
    {
    }

    /**
     * @return string
     */
    public function getMorphClass(): string
    {
        return 'guest';
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->visitorId;
    }
}
