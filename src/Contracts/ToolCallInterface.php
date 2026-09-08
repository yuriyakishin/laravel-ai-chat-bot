<?php

namespace Yu\AiChatBot\Contracts;

interface ToolCallInterface
{
    /**
     * The provider-assigned identifier for this tool call.
     *
     * @return string
     */
    public function id(): string;

    /**
     * The name of the tool being called.
     *
     * @return string
     */
    public function name(): string;

    /**
     * The arguments passed to the tool.
     *
     * @return array
     */
    public function arguments(): array;
}
