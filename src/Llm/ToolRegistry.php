<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm;

use Yu\AiChatBot\Contracts\ToolInterface;

class ToolRegistry
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /**
     * @param ToolInterface $tool
     * @return void
     */
    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->name()] = $tool;
    }

    /**
     * @param string $name
     * @return ToolInterface
     */
    public function get(string $name): ToolInterface
    {
        return $this->tools[$name] ?? throw new \OutOfBoundsException("Unknown tool: {$name}");
    }

    /** @return array<int, array{type: string, function: array}> */
    public function definitions(): array
    {
        return array_values(array_map(
            static fn(ToolInterface $tool): array => [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $tool->parameters(),
                ],
            ],
            $this->tools
        ));
    }
}
