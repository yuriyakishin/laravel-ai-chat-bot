<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Chat;

use Yu\AiChatBot\Contracts\ChatServiceInterface;
use Yu\AiChatBot\Contracts\ChatResultInterface;
use Yu\AiChatBot\DTO\ChatResult;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Models\TelegramMessage;
use Yu\AiChatBot\Telegram\TelegramClient;

class TelegramChatService implements ChatServiceInterface
{
    /**
     * @param TelegramClient $telegramClient
     */
    public function __construct(
        private TelegramClient $telegramClient,
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
        $conversation = Conversation::where('uuid', $conversationUuid)->firstOrFail();
        $conversation->messages()->create(['role' => 'user', 'content' => $message]);

        $replyToMessageId = TelegramMessage::where('conversation_id',
            $conversation->id)->latest('id')->value('telegram_message_id');

        $messageId = $this->telegramClient->sendMessage($message, $replyToMessageId);

        TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'telegram_message_id' => $messageId,
        ]);

        return new ChatResult(
            reply: '',
            conversationUuid: $conversationUuid,
            promptTokens: 0,
            completionTokens: 0,
        );
    }
}
