<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
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
     * @return string
     */
    public function adminChatId(): string
    {
        return $this->adminChatId;
    }

    /**
     * @param string $text
     * @param int|null $replyToMessageId
     * @return int
     * @throws ConnectionException
     * @throws RequestException
     */
    public function sendMessage(
        string $text,
        ?int $replyToMessageId =
        null
    ): int {
        $payload = ['chat_id' => $this->adminChatId, 'text' => $text];
        if ($replyToMessageId !== null) {
            $payload['reply_parameters'] = [
                'message_id' => $replyToMessageId,
                'allow_sending_without_reply' => true,
            ];
        }

        try {
            $response = Http::retry(2, 200)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload)
                ->throw();
        } catch (ConnectionException $e) {
            throw new ConnectionException($this->redact($e->getMessage()), $e->getCode());
        }

        return $response->json('result.message_id');
    }

    /**
     * @param string $message
     * @return string
     */
    private function redact(string $message): string
    {
        return $this->botToken === '' ? $message : str_replace($this->botToken, '***', $message);
    }
}
