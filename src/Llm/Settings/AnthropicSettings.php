<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Settings;

use Yu\AiChatBot\Contracts\LlmSettingsInterface;

class AnthropicSettings implements LlmSettingsInterface
{
    /**
     * @return string
     */
    public function endpoint(): string
    {
        return config('ai-chat.llm.anthropic.endpoint');
    }

    /**
     * @return string
     */
    public function apiKey(): string
    {
        return config('ai-chat.llm.anthropic.api_key');
    }

    /**
     * @return string
     */
    public function model(): string
    {
        return config('ai-chat.llm.anthropic.model');
    }

    /**
     * @return float
     */
    public function temperature(): float
    {
        return (float)config('ai-chat.llm.anthropic.temperature');
    }

    /**
     * @return int
     */
    public function maxTokens(): int
    {
        return (int)config('ai-chat.llm.anthropic.max_tokens');
    }
}
