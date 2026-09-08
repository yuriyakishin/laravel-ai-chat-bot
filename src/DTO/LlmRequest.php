<?php

declare(strict_types=1);

namespace Yu\AiChatBot\DTO;

use Yu\AiChatBot\Contracts\LlmRequestInterface;

final readonly class LlmRequest implements LlmRequestInterface
{
    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<int, array{type: string, function: array}> $tools
     */
    public function __construct(
        private array $messages,
        private array $tools = []
    ) {
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * @return array<int, array{type: string, function: array}>
     */
    public function tools(): array
    {
        return $this->tools;
    }
}
