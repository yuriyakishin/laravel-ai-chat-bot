<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Chat;

use Yu\AiChatBot\Contracts\ChatResultInterface;
use Yu\AiChatBot\Contracts\ChatServiceInterface;
use Yu\AiChatBot\Models\Conversation;

class RoutingChatService implements ChatServiceInterface
{
    /**
     * @param AiChatService $aiChatService
     * @param TelegramChatService $telegramChatService
     */
    public function __construct(
        private AiChatService $aiChatService,
        private TelegramChatService $telegramChatService
    ) {
    }

    /**
     * @param mixed $owner
     * @param string $conversationUuid
     * @param string $message
     * @return ChatResultInterface
     */
    public function send(mixed $owner, string $conversationUuid, string $message): ChatResultInterface
    {
        $status = Conversation::where('uuid', $conversationUuid)->value('status');

        return $status === 'human'
            ? $this->telegramChatService->send($owner, $conversationUuid, $message)
            : $this->aiChatService->send($owner, $conversationUuid, $message);
    }
}
