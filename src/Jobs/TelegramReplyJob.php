<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Models\TelegramMessage;
use Yu\AiChatBot\Models\Message;
use Yu\AiChatBot\Telegram\TelegramClient;

class TelegramReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param array $payload
     */
    public function __construct(private array $payload)
    {
    }

    /**
     * @param TelegramClient $telegramClient
     * @return void
     */
    public function handle(TelegramClient $telegramClient)
    {
        $chatId = $this->payload['message']['chat']['id'] ?? null;

        if ((string)$chatId !== (string)config('ai-chat.telegram.admin_chat_id')) {
            Log::channel('ai-chat')->warning('dropped Telegram update from a non-admin chat',
                ['chat_id' => $chatId]);
            return;
        }

        $messageId = $this->payload['message']['message_id'] ?? null;
        $replyToMessageId = $this->payload['message']['reply_to_message']['message_id'] ?? null;

        if ($messageId === null) {
            Log::channel('ai-chat')->warning('dropped Telegram update without message_id',
                ['payload' => $this->payload]);
            return;
        }

        if ($replyToMessageId === null) {
            Log::channel('ai-chat')->warning('dropped Telegram reply without reply_to_message',
                ['payload' => $this->payload]);
            $this->warnAdmin($telegramClient, $messageId);
            return;
        }

        /** @var TelegramMessage $telegramMessage */
        $telegramMessage = TelegramMessage::where('telegram_message_id', $replyToMessageId)->first();

        if ($telegramMessage === null) {
            Log::channel('ai-chat')->warning('dropped Telegram reply with no matching telegram_message_id',
                ['reply_to_message_id' => $replyToMessageId,]);
            return;
        }

        /** @var Conversation $conversation */
        $conversation = $telegramMessage->conversation;

        if ($conversation === null) {
            Log::channel('ai-chat')->warning('dropped Telegram reply - conversation no longer exists', [
                'reply_to_message_id' => $replyToMessageId,
                'conversation_id' =>
                    $telegramMessage->conversation_id,
            ]);
            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => $this->payload['message']['text'] ?? '',
        ]);

        TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'telegram_message_id' => $messageId,
        ]);
    }

    /**
     * @param TelegramClient $telegramClient
     * @param int $messageId
     * @return void
     */
    private function warnAdmin(TelegramClient $telegramClient, int $messageId): void
    {
        try {
            $telegramClient->sendMessage(
                "⚠️ Message not delivered. Use Reply on a customer's message so it reaches the right chat.",
                $messageId
            );
        } catch (ConnectionException|RequestException $e) {
            Log::channel('ai-chat')->error('failed to send missing-reply warning to Telegram admin', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
