<?php

use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\DTO\LlmRequest;
use Yu\AiChatBot\Llm\Providers\AnthropicProvider;
use Yu\AiChatBot\Llm\Settings\AnthropicSettings;

beforeEach(function () {
    config(['ai-chat.llm.anthropic.api_key' => 'test-key']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'Done']],
            'usage' => ['input_tokens' => 1, 'output_tokens' => 1],
        ], 200),
    ]);
});

function anthropicTools(): array
{
    return [[
        'type' => 'function',
        'function' => ['name' => 'current_datetime', 'description' => 'Now', 'parameters' => ['type' => 'object']],
    ]];
}

it('sends tools without tool_choice when tool calls are allowed', function () {
    $provider = new AnthropicProvider(new AnthropicSettings());
    $provider->send(new LlmRequest([['role' => 'user', 'content' => 'hi']], anthropicTools()));

    Http::assertSent(fn($request) => $request['tools'][0]['name'] === 'current_datetime'
        && !isset($request['tool_choice']));
});

it('forbids tool calls with tool_choice none when the request does not allow them', function () {
    $provider = new AnthropicProvider(new AnthropicSettings());
    $provider->send(new LlmRequest([['role' => 'user', 'content' => 'hi']], anthropicTools(), allowToolCalls: false));

    Http::assertSent(fn($request) => $request['tools'][0]['name'] === 'current_datetime'
        && $request['tool_choice'] === ['type' => 'none']);
});
