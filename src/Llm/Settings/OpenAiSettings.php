<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Settings;

use Yu\AiChatBot\Contracts\LlmSettingsInterface;

class OpenAiSettings implements LlmSettingsInterface
{
    /**
     * @return string
     */
    public function endpoint(): string
    {
        return config('ai-chat.llm.open_ai.endpoint');
    }

    /**
     * @return string
     */
    public function apiKey(): string
    {
        return config('ai-chat.llm.open_ai.api_key');
    }

    /**
     * @return string
     */
    public function model(): string
    {
        return config('ai-chat.llm.open_ai.model');
    }

    /**
     * @return float
     */
    public function temperature(): float
    {
        return (float)config('ai-chat.llm.open_ai.temperature');
    }

    /**
     * @return int
     */
    public function maxTokens(): int
    {
        return (int)config('ai-chat.llm.open_ai.max_tokens');
    }
}
