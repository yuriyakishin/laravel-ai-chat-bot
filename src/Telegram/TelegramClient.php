<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Telegram;

use Illuminate\Support\Facades\Http;

class TelegramClient
{
    /**
     * @param string $botToken
     * @param string $adminChatId
     */
    public function __construct(
        private readonly string $botToken,
        private readonly string $adminChatId,
    ) {
    }

    /**
     * @param string $text
     * @param int|null $replyToMessageId
     * @return int
     */
    public function sendMessage(
        string $text,
        ?int $replyToMessageId =
        null
    ): int {
        $payload = ['chat_id' => $this->adminChatId, 'text' => $text];
        if ($replyToMessageId !== null) {
            $payload['reply_to_message_id'] = $replyToMessageId;
        }

        $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload)->throw();

        return $response->json('result.message_id');
    }
}
