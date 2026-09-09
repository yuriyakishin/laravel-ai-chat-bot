<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
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
            Log::warning('ai-chat: rejected Telegram webhook — secret token mismatch', [
                'header_present' => $request->hasHeader('X-Telegram-Bot-Api-Secret-Token'),
            ]);
            abort(403);
        }

        Log::info('ai-chat: received Telegram webhook update', ['update' => $request->all()]);

        $replyToMessageId = $request->input('message.reply_to_message.message_id');

        if ($replyToMessageId === null) {
            Log::warning('ai-chat: dropped Telegram update without reply_to_message', [
                'update' => $request->all(),
            ]);
            return response()->noContent();
        }

        $telegramMessage = TelegramMessage::where('telegram_message_id', $replyToMessageId)->first();

        if ($telegramMessage === null) {
            Log::warning('ai-chat: dropped Telegram reply with no matching telegram_message_id', [
                'reply_to_message_id' => $replyToMessageId,
            ]);
            return response()->noContent();
        }

        $conversation = Conversation::find($telegramMessage->conversation_id);

        if ($conversation === null) {
            Log::warning('ai-chat: dropped Telegram reply — conversation no longer exists', [
                'reply_to_message_id' => $replyToMessageId,
                'conversation_id' => $telegramMessage->conversation_id,
            ]);
            return response()->noContent();
        }

        $conversation->messages()->create([
            'role' => 'admin',
            'content' => $request->input('message.text', ''),
        ]);

        TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'telegram_message_id' =>
                $request->input('message.message_id'),
        ]);

        Log::info('ai-chat: routed Telegram reply to conversation', [
            'reply_to_message_id' => $replyToMessageId,
            'matched_telegram_message_row_id' => $telegramMessage->id,
            'conversation_id' => $conversation->id,
            'conversation_uuid' => $conversation->uuid,
        ]);

        return response()->noContent();
    }
}
