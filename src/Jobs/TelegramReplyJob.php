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
use Yu\AiChatBot\Events\AdminMessageSentEvent;

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
                ['update_id' => $this->payload['update_id'] ?? null]);
            return;
        }

        if ($replyToMessageId === null) {
            Log::channel('ai-chat')->warning('dropped Telegram reply without reply_to_message',
                ['message_id' => $messageId]);
            $this->warnAdmin(
                $telegramClient,
                $messageId,
                "⚠️ Message not delivered. Use Reply on a customer's message so it reaches the right chat."
            );
            return;
        }

        /** @var TelegramMessage $telegramMessage */
        $telegramMessage = TelegramMessage::where('telegram_message_id', $replyToMessageId)
            ->where(fn($query) => $query->where('chat_id', $chatId)->orWhereNull('chat_id'))
            ->latest('id')
            ->first();

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

        $text = $this->payload['message']['text'] ?? '';

        if ($text === '') {
            Log::channel('ai-chat')->warning('dropped Telegram reply without text', ['message_id' => $messageId]);
            $this->warnAdmin(
                $telegramClient,
                $messageId,
                '⚠️ Message not delivered. Only text messages can be sent to the customer.'
            );
            return;
        }

        if ($conversation->status === 'closed') {
            Log::channel('ai-chat')->warning('dropped Telegram reply - conversation is closed', [
                'conversation_id' => $conversation->id,
            ]);
            $this->warnAdmin(
                $telegramClient,
                $messageId,
                '⚠️ Message not delivered. The customer has closed this chat.'
            );
            return;
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'admin',
            'content' => $text,
        ]);

        TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'chat_id' => $chatId,
            'telegram_message_id' => $messageId,
        ]);

        AdminMessageSentEvent::dispatch($message);
    }

    /**
     * @param TelegramClient $telegramClient
     * @param int $messageId
     * @param string $warning
     * @return void
     */
    private function warnAdmin(TelegramClient $telegramClient, int $messageId, string $warning): void
    {
        try {
            $telegramClient->sendMessage($warning, $messageId);
        } catch (ConnectionException|RequestException $e) {
            Log::channel('ai-chat')->error('failed to send warning to Telegram admin', [
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
