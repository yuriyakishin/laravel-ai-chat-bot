<?php

use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\Chat\AiChatService;
use Yu\AiChatBot\Chat\CurrentConversation;
use Yu\AiChatBot\Contracts\ChatResultInterface;
use Yu\AiChatBot\Llm\Settings\OpenAiSettings;
use Yu\AiChatBot\Llm\Providers\OpenAiProvider;
use Yu\AiChatBot\Llm\ToolRegistry;

it('creates a conversation on first contact and persists both messages', function () {
    config(['ai-chat.llm.open_ai.api_key' => 'test-key']);

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

    $settings = new OpenAiSettings();
    $provider = new OpenAiProvider($settings);
    $chatService = new AiChatService($provider, new ToolRegistry(), new CurrentConversation());
    $response = $chatService->send(...$request);

    expect($response)->toBeInstanceOf(ChatResultInterface::class)
        ->and($response->promptTokens())->toBe(12)
        ->and($response->completionTokens())->toBe(3)
        ->and($response->conversationUuid())->toBe('conversationUuid')
        ->and($response->reply())->toBe('Hello!');
});
