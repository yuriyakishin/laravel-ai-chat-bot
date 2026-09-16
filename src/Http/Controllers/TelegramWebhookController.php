<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Yu\AiChatBot\Jobs\TelegramReplyJob;

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

        if (config('ai-chat.use_queue')) {
            TelegramReplyJob::dispatch($request->all());
        } else {
            TelegramReplyJob::dispatchSync($request->all());
        }

        return response()->noContent();
    }
}
