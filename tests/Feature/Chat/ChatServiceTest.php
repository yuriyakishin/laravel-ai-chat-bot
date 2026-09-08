<?php

use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\Chat\AiChatService;
use Yu\AiChatBot\Contracts\ChatResultInterface;
use Yu\AiChatBot\Llm\LlmSettings;
use Yu\AiChatBot\Llm\Providers\OpenAiProvider;

it('creates a conversation on first contact and persists both messages', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [
                ['message' => ['role' => 'assistant', 'content' => 'Hello!']],
            ],
            'usage' => ['prompt_tokens' => 12, 'completion_tokens' => 3],
        ], 200)
    ]);

    $owner = new class {
        public function getMorphClass()
        {
            return 'user';
        }

        public function getKey()
        {
            return 1;
        }
    };

    $request = [
        'owner' => $owner,
        'conversationUuid' => 'conversationUuid',
        'message' => 'Hello!',
    ];

    $settings = new LlmSettings();
    $provider = new OpenAiProvider($settings);
    $chatService = new AiChatService($provider);
    $response = $chatService->send(...$request);

    expect($response)->toBeInstanceOf(ChatResultInterface::class)
        ->and($response->promptTokens())->toBe(12)
        ->and($response->completionTokens())->toBe(3)
        ->and($response->conversationUuid())->toBe('conversationUuid')
        ->and($response->reply())->toBe('Hello!');
});
