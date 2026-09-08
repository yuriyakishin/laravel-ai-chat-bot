<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Exceptions;

use RuntimeException;
use Throwable;

final class LlmProviderException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $retryable,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, previous: $previous);
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
