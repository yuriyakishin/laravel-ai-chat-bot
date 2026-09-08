<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Settings;

use Yu\AiChatBot\Contracts\LlmSettingsInterface;
use Yu\AiChatBot\Models\LlmSetting;

class OpenAiDatabaseSettings implements LlmSettingsInterface
{
    private ?array $settings = null;

    /**
     * @return string
     */
    public function endpoint(): string
    {
        return $this->settings()['endpoint'];
    }

    /**
     * @return string
     */
    public function apiKey(): string
    {
        return $this->settings()['api_key'];
    }

    /**
     * @return string
     */
    public function model(): string
    {
        return $this->settings()['model'];
    }

    /**
     * @return float
     */
    public function temperature(): float
    {
        return (float)$this->settings()['temperature'];
    }

    /**
     * @return int
     */
    public function maxTokens(): int
    {
        return (int)$this->settings()['max_tokens'];
    }

    /**
     * @return array
     */
    private function settings(): array
    {
        return $this->settings ??= LlmSetting::where('provider', 'open_ai')->firstOrFail()->settings;
    }
}
