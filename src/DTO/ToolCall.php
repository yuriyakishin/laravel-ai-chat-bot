<?php

declare(strict_types=1);

namespace Yu\AiChatBot\DTO;

use Yu\AiChatBot\Contracts\ToolCallInterface;

final readonly class ToolCall implements ToolCallInterface
{
    /**
     * @param string $id
     * @param string $name
     * @param array $arguments
     */
    public function __construct(
        private string $id,
        private string $name,
        private array $arguments,
    ) {
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function arguments(): array
    {
        return $this->arguments;
    }
}
