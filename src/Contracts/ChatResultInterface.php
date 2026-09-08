<?php

namespace Yu\AiChatBot\Contracts;

interface ChatResultInterface
{
    /**
     * The assistant's reply text.
     *
     * @return string
     */
    public function reply(): string;

    /**
     * The UUID of the conversation this result belongs to.
     *
     * @return string
     */
    public function conversationUuid(): string;

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
}
