<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Facades;

use Illuminate\Support\Facades\Facade;
use Yu\AiChatBot\Contracts\ChatServiceInterface;

class AiChat extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ChatServiceInterface::class;
    }
}
