<?php

use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\DTO\LlmRequest;
use Yu\AiChatBot\Exceptions\LlmProviderException;
use Yu\AiChatBot\Llm\Settings\OpenAiSettings;
use Yu\AiChatBot\Llm\Providers\OpenAiProvider;

beforeEach(function () {
    config(['ai-chat.llm.open_ai.api_key' => 'test-key']);
});

it('turns a successful OpenAI response into an LlmResponse', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'Hello!']],
            ],
            'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 3],
        ], 200),
    ]);

    $settings = new OpenAiSettings();
    $provider = new OpenAiProvider($settings);
    $request = new LlmRequest(
        messages: [['role' => 'user', 'content' => 'hi']]
    );

    $response = $provider->send($request);

    expect($response->content())->toBe('Hello!')
        ->and($response->promptTokens())->toBe(12)
        ->and($response->completionTokens())->toBe(3);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'gpt-4o-mini';
    });
});

it('throws a non-retryable exception on a 4xx response', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['error' => ['message' => 'bad request']], 400),
    ]);

    $settings = new OpenAiSettings();
    $provider = new OpenAiProvider($settings);
    $request = new LlmRequest([['role' => 'user', 'content' => 'hi']]);

    try {
        $provider->send($request);
        $this->fail('Expected LlmProviderException');
    } catch (LlmProviderException $e) {
        expect($e->isRetryable())->toBeFalse();
    }
});

it('throws a retryable exception on a 5xx response', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['error' => ['message' => 'server error']], 500),
    ]);

    $settings = new OpenAiSettings();
    $provider = new OpenAiProvider($settings);
    $request = new LlmRequest([['role' => 'user', 'content' => 'hi']]);

    try {
        $provider->send($request);
        $this->fail('Expected LlmProviderException');
    } catch (LlmProviderException $e) {
        expect($e->isRetryable())->toBeTrue();
    }
});
