<?php

namespace Yu\AiChatBot\Contracts;

interface LlmRequestInterface
{
    /**
     * The conversation messages to send to the LLM.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function messages(): array;

    /**
     * The tool definitions to make available to the LLM.
     *
     * @return array<int, array{type: string, function: array}>
     */
    public function tools(): array;

    /**
     * Whether the LLM may call the tools; when false the tools are only sent so the history stays valid.
     *
     * @return bool
     */
    public function allowToolCalls(): bool;
}
