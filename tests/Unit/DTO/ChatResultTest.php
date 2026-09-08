<?php

use Yu\AiChatBot\DTO\ChatResult;

it('exposes its constructor values through the interface methods',
    function () {
        $result = new ChatResult(
            reply: 'hello there',
            conversationUuid: 'abc-123',
            promptTokens: 10,
            completionTokens: 5,
        );

        expect($result->reply())->toBe('hello there')
            ->and($result->conversationUuid())->toBe('abc-123')
            ->and($result->promptTokens())->toBe(10)
            ->and($result->completionTokens())->toBe(5);
    });
