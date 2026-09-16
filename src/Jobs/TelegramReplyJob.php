<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Models\TelegramMessage;
use Yu\AiChatBot\Models\Message;

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
     * @return void
     */
    public function handle()
    {
        $replyToMessageId = $this->payload['message']['reply_to_message']['message_id'] ?? null;

        if ($replyToMessageId === null) {
            Log::warning('ai-chat: dropped Telegram reply without reply_to_message',
                ['payload' => $this->payload]);
            return;
        }

        /** @var TelegramMessage $telegramMessage */
        $telegramMessage = TelegramMessage::where('telegram_message_id', $replyToMessageId)->first();

        if ($telegramMessage === null) {
            Log::warning('ai-chat: dropped Telegram reply with no matching telegram_message_id',
                ['reply_to_message_id' => $replyToMessageId,]);
            return;
        }

        /** @var Conversation $conversation */
        $conversation = $telegramMessage->conversation;

        if ($conversation === null) {
            Log::warning('ai-chat: dropped Telegram reply - conversation no longer exists', [
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
            'telegram_message_id' => $this->payload['message']['message_id'] ?? null,
        ]);
    }
}
