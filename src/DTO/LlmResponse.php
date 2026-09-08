<?php

declare(strict_types=1);

namespace Yu\AiChatBot\DTO;

use Yu\AiChatBot\Contracts\LlmResponseInterface;
use Yu\AiChatBot\Contracts\ToolCallInterface;

final readonly class LlmResponse implements LlmResponseInterface
{
    /**
     * @param string $content
     * @param int $promptTokens
     * @param int $completionTokens
     * @param ToolCallInterface[] $toolCalls
     */
    public function __construct(
        private string $content,
        private int $promptTokens,
        private int $completionTokens,
        private array $toolCalls = [],
    ) {
    }

    /**
     * @return string
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * @return int
     */
    public function promptTokens(): int
    {
        return $this->promptTokens;
    }

    /**
     * @return int
     */
    public function completionTokens(): int
    {
        return $this->completionTokens;
    }

    /**
     * @return ToolCallInterface[]
     */
    public function toolCalls(): array
    {
        return $this->toolCalls;
    }
}
