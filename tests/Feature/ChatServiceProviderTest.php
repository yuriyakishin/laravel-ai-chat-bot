<?php

use Yu\AiChatBot\Contracts\LlmProviderInterface;

it('fails with a clear message only when an unknown LLM provider is actually used', function () {
    config(['ai-chat.default' => 'openai']);

    expect(fn() => app(LlmProviderInterface::class))
        ->toThrow(InvalidArgumentException::class, 'Unknown AI chat LLM provider [openai]');
});
