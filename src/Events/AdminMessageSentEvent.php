<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Yu\AiChatBot\Models\Message;

class AdminMessageSentEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param Message $message
     */
    public function __construct(public readonly Message $message)
    {
    }
}
