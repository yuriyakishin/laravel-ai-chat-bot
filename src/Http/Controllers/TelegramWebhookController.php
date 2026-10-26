<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;
use Yu\AiChatBot\Jobs\TelegramReplyJob;

class TelegramWebhookController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        $secret = (string)config('ai-chat.telegram.webhook_secret');
        $header = (string)$request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === '' || !hash_equals($secret, $header)) {
            Log::channel('ai-chat')->warning('rejected Telegram webhook — secret token mismatch', [
                'header_present' => $request->hasHeader('X-Telegram-Bot-Api-Secret-Token'),
                'secret_configured' => $secret !== '',
            ]);
            abort(403);
        }

        Log::channel('ai-chat')->info('received Telegram webhook update', [
            'update_id' => $request->input('update_id'),
            'chat_id' => $request->input('message.chat.id'),
            'message_id' => $request->input('message.message_id'),
            'reply_to_message_id' => $request->input('message.reply_to_message.message_id'),
        ]);

        if (config('ai-chat.use_queue')) {
            TelegramReplyJob::dispatch($request->all());
        } else {
            try {
                TelegramReplyJob::dispatchSync($request->all());
            } catch (Throwable $e) {
                Log::channel('ai-chat')->error('failed to process Telegram webhook update', [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return response()->noContent();
    }
}
