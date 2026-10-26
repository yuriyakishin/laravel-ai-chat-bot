<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\Telegram\TelegramClient;

it('replies with allow_sending_without_reply so a deleted message does not block delivery', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 42]])]);

    $messageId = (new TelegramClient('123:secret-token', '-100200'))->sendMessage('hello', 7);

    expect($messageId)->toBe(42);
    Http::assertSent(fn($request) => $request['chat_id'] === '-100200'
        && $request['reply_parameters'] === ['message_id' => 7, 'allow_sending_without_reply' => true]);
});

it('removes the bot token from connection error messages', function () {
    Http::fake(fn($request) => throw new ConnectionException(
        "cURL error 28: Operation timed out for {$request->url()}"
    ));

    try {
        (new TelegramClient('123:secret-token', '-100200'))->sendMessage('hello');
        $this->fail('Expected ConnectionException');
    } catch (ConnectionException $e) {
        expect($e->getMessage())->not->toContain('secret-token')
            ->and($e->getMessage())->toContain('/bot***/sendMessage')
            ->and($e->getPrevious())->toBeNull();
    }
});
