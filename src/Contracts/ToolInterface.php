<?php

namespace Yu\AiChatBot\Contracts;

interface ToolInterface
{
    /**
     * The tool's unique name, as exposed to the LLM.
     *
     * @return string
     */
    public function name(): string;

    /**
     * A description of what the tool does, shown to the LLM.
     *
     * @return string
     */
    public function description(): string;

    /**
     * The JSON Schema describing the tool's parameters.
     *
     * @return array
     */
    public function parameters(): array;

    /**
     * Executes the tool with the given arguments and returns its result.
     *
     * @param array $arguments
     * @return array
     */
    public function execute(array $arguments): array;
}
