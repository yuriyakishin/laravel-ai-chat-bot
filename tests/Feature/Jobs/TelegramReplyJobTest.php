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
        && $request['reply_parameters']['message_id'] === 99
        && str_contains($request['text'], 'Use Reply'));
});

it('routes a reply by telegram_message_id within the admin chat only', function () {
    config(['ai-chat.telegram.admin_chat_id' => '-100200']);

    $oldChatConversation = Conversation::create([
        'uuid' => 'conv-old-chat',
        'owner_type' => 'App\\Models\\User',
        'owner_id' => 1,
    ]);
    $adminChatConversation = Conversation::create([
        'uuid' => 'conv-admin-chat',
        'owner_type' => 'App\\Models\\User',
        'owner_id' => 2,
    ]);

    TelegramMessage::create([
        'conversation_id' => $adminChatConversation->id,
        'chat_id' => -100200,
        'telegram_message_id' => 57,
    ]);
    TelegramMessage::create([
        'conversation_id' => $oldChatConversation->id,
        'chat_id' => -100999,
        'telegram_message_id' => 57,
    ]);

    (new TelegramReplyJob([
        'message' => [
            'message_id' => 58,
            'chat' => ['id' => -100200],
            'text' => 'answer for the right customer',
            'reply_to_message' => ['message_id' => 57],
        ],
    ]))->handle(app(TelegramClient::class));

    expect($oldChatConversation->messages()->count())->toBe(0)
        ->and($adminChatConversation->messages()->value('content'))->toBe('answer for the right customer')
        ->and(TelegramMessage::where('telegram_message_id', 58)->value('chat_id'))->toBe(-100200);
});

it('warns the admin and stores nothing when the reply has no text', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 100]])]);

    $conversation = Conversation::create(['uuid' => 'conv-1', 'owner_type' => 'guest', 'owner_id' => 'visitor-1']);
    TelegramMessage::create(['conversation_id' => $conversation->id, 'telegram_message_id' => 18]);

    (new TelegramReplyJob([
        'message' => [
            'message_id' => 99,
            'photo' => [['file_id' => 'abc']],
            'reply_to_message' => ['message_id' => 18],
        ],
    ]))->handle(app(TelegramClient::class));

    expect(Message::count())->toBe(0);
    Http::assertSent(fn(Request $request) => str_contains($request['text'], 'Only text messages'));
});

it('warns the admin and stores nothing when the customer closed the chat', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 100]])]);

    $conversation = Conversation::create([
        'uuid' => 'conv-1',
        'owner_type' => 'guest',
        'owner_id' => 'visitor-1',
        'status' => 'closed',
    ]);
    TelegramMessage::create(['conversation_id' => $conversation->id, 'telegram_message_id' => 18]);

    (new TelegramReplyJob([
        'message' => [
            'message_id' => 99,
            'text' => 'are you still there?',
            'reply_to_message' => ['message_id' => 18],
        ],
    ]))->handle(app(TelegramClient::class));

    expect(Message::count())->toBe(0);
    Http::assertSent(fn(Request $request) => str_contains($request['text'], 'closed this chat'));
});

