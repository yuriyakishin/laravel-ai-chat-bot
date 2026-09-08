<?php

namespace Yu\AiChatBot\Contracts;

interface LlmProviderInterface
{
    /**
     * Sends a request to the LLM provider and returns its response.
     *
     * @param LlmRequestInterface $request
     * @return LlmResponseInterface
     */
    public function send(LlmRequestInterface $request): LlmResponseInterface;
}
