<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Tools;

use Yu\AiChatBot\Contracts\ToolInterface;

class CurrentDateTimeTool implements ToolInterface
{
    /**
     * @return string
     */
    public function name(): string
    {
        return 'current_datetime';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Returns the current date and time.';
    }

    /**
     * @return array
     */
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    /**
     * @param array $arguments
     * @return array
     */
    public function execute(array $arguments): array
    {
        return ['datetime' => now()->toIso8601String()];
    }
}
