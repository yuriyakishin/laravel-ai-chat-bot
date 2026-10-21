<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\Jobs\TelegramReplyJob;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Models\Message;
use Yu\AiChatBot\Models\TelegramMessage;
use Yu\AiChatBot\Telegram\TelegramClient;

it('creates a message and telegram_message record for a matched reply', function () {
    $conversation = Conversation::create([
        'uuid' => 'conv-1',
        'owner_type' => 'App\\Models\\User',
        'owner_id' => 1,
    ]);

    TelegramMessage::create([
        'conversation_id' => $conversation->id,
        'telegram_message_id' => 18
    ]);

    (new TelegramReplyJob([
        'message' => [
            'message_id' => 99,
            'text' => 'hello from job test',
            'reply_to_message' => ['message_id' => 18],
        ],
    ]))->handle(app(TelegramClient::class));

    $message = $conversation->messages()->first();
    expect($message)->not->toBeNull()->and($message->content)->toBe('hello from job test');

    expect(TelegramMessage::where('telegram_message_id', 99)
        ->where('conversation_id', $conversation->id)->exists())->toBeTrue();
});

it('warns the admin in Telegram when a message is sent without Reply', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 100]])]);

    (new TelegramReplyJob([
        'message' => [
            'message_id' => 99,
            'text' => 'plain message without reply',
        ],
    ]))->handle(app(TelegramClient::class));

    expect(Message::count())->toBe(0);

    Http::assertSent(fn(Request $request) => str_contains($request->url(), '/sendMessage')
        && $request['reply_to_message_id'] === 99
        && str_contains($request['text'], 'Use Reply'));
});
