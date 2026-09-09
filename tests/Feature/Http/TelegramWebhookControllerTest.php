<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Models\TelegramMessage;

beforeEach(function () {
    config(['ai-chat.telegram.webhook_secret' => 'test-secret']);
});

function postTelegramWebhook(array $payload, string $secret = 'test-secret')
{
    return test()->postJson(
        route('ai-chat.telegram-webhook'),
        $payload,
        ['X-Telegram-Bot-Api-Secret-Token' => $secret]
    );
}

it('rejects an update with a mismatched secret token', function () {
    Log::spy();

    $response = postTelegramWebhook(['message' => []], secret: 'wrong-secret');

    $response->assertStatus(403);
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn(string $message) => str_contains($message, 'secret token mismatch'));
});

it('drops an update with no reply_to_message', function () {
    Log::spy();

    $response = postTelegramWebhook([
        'message' => ['message_id' => 99, 'text' => 'hello'],
    ]);

    $response->assertStatus(204);
    expect(\Yu\AiChatBot\Models\Message::count())->toBe(0);
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn(string $message) => str_contains($message, 'without reply_to_message'));
});

it('drops a reply with no matching telegram_message_id', function () {
    Log::spy();

    $response = postTelegramWebhook([
        'message' => [
            'message_id' => 99,
            'text' => 'hello',
            'reply_to_message' => ['message_id' => 12345],
        ],
    ]);

    $response->assertStatus(204);
    expect(\Yu\AiChatBot\Models\Message::count())->toBe(0);
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn(string $message) => str_contains($message, 'no matching telegram_message_id'));
});

it('drops a reply whose conversation no longer exists', function () {
    Log::spy();

    Schema::disableForeignKeyConstraints();
    TelegramMessage::create(['conversation_id' => 999999, 'telegram_message_id' => 18]);
    Schema::enableForeignKeyConstraints();

    $response = postTelegramWebhook([
        'message' => [
            'message_id' => 99,
            'text' => 'hello',
            'reply_to_message' => ['message_id' => 18],
        ],
    ]);

    $response->assertStatus(204);
    expect(\Yu\AiChatBot\Models\Message::count())->toBe(0);
    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn(string $message) => str_contains($message, 'conversation no longer exists'));
});

it('routes a valid admin reply to the matched conversation', function () {
    Log::spy();

    $conversation = Conversation::create([
        'uuid' => 'conv-1',
        'owner_type' => 'App\\Models\\User',
        'owner_id' => 1,
    ]);
    TelegramMessage::create(['conversation_id' => $conversation->id, 'telegram_message_id' => 18]);

    $response = postTelegramWebhook([
        'message' => [
            'message_id' => 99,
            'text' => 'Так, вакцинація комплексна Нобівак',
            'reply_to_message' => ['message_id' => 18],
        ],
    ]);

    $response->assertStatus(204);

    $message = $conversation->messages()->first();
    expect($message)->not->toBeNull()
        ->and($message->role)->toBe('admin')
        ->and($message->content)->toBe('Так, вакцинація комплексна Нобівак');

    expect(TelegramMessage::where('telegram_message_id', 99)
        ->where('conversation_id', $conversation->id)->exists())->toBeTrue();

    Log::shouldHaveReceived('info')
        ->withArgs(fn(string $message) => str_contains($message, 'routed Telegram reply to conversation'));
});
