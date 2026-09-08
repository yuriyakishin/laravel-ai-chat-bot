<?php

namespace Yu\AiChatBot\Contracts;

interface LlmSettingsInterface
{
    /**
     * The LLM provider's API endpoint URL.
     *
     * @return string
     */
    public function endpoint(): string;

    /**
     * The API key used to authenticate with the LLM provider.
     *
     * @return string
     */
    public function apiKey(): string;

    /**
     * The model name to use for requests.
     *
     * @return string
     */
    public function model(): string;

    /**
     * The sampling temperature to use for requests.
     *
     * @return float
     */
    public function temperature(): float;

    /**
     * The maximum number of tokens to generate.
     *
     * @return int
     */
    public function maxTokens(): int;
}
