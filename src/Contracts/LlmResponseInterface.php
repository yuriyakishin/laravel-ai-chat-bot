<?php

namespace Yu\AiChatBot\Contracts;

interface LlmResponseInterface
{
    /**
     * The text content of the LLM's response.
     *
     * @return string
     */
    public function content(): string;

    /**
     * Number of tokens used by the prompt sent to the LLM.
     *
     * @return int
     */
    public function promptTokens(): int;

    /**
     * Number of tokens used by the LLM's completion.
     *
     * @return int
     */
    public function completionTokens(): int;

    /**
     * The tool calls requested by the LLM, if any.
     *
     * @return ToolCallInterface[]
     */
    public function toolCalls(): array;
}
