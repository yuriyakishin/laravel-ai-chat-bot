<?php

use Yu\AiChatBot\Exceptions\LlmProviderException;

it('reports whether it is retryable', function () {
    $retryable = new LlmProviderException('server error', retryable: true);
    $notRetryable = new LlmProviderException('bad request', retryable: false);

    expect($retryable->isRetryable())->toBeTrue()
        ->and($notRetryable->isRetryable())->toBeFalse()
        ->and($retryable)->toBeInstanceOf(RuntimeException::class);
});
