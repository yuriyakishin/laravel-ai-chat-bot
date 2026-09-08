<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Yu\AiChatBot\Models\TelegramMessage;
use Yu\AiChatBot\Models\Conversation;

class TelegramWebhookController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        if ($request->header('X-Telegram-Bot-Api-Secret-Token') !== config('ai-chat.telegram.webhook_secret')) {
            abort(403);
        }

        $replyToMessageId = $request->input('message.reply_to_message.message_id');

        if ($replyToMessageId === null) {
            return response()->noContent();
        }

        $telegramMessage = TelegramMessage::where('telegram_message_id', $replyToMessageId)->first();

        if ($telegramMessage === null) {
            return response()->noContent();
        }

        $conversation = Conversation::find($telegramMessage->conversation_id);

        $conversation->messages()->create([
            'role' => 'admin',
            'content' => $request->input('message.text', ''),
        ]);

        TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'telegram_message_id' =>
                $request->input('message.message_id'),
        ]);

        return response()->noContent();
    }
}
