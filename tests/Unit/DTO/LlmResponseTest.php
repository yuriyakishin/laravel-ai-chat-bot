<?php

use Yu\AiChatBot\DTO\LlmResponse;

it('exposes its constructor values through the interface methods',
    function () {
        $response = new LlmResponse(
            content: 'hello there',
            promptTokens: 10,
            completionTokens: 5,
        );

        expect($response->content())->toBe('hello there')
            ->and($response->promptTokens())->toBe(10)
            ->and($response->completionTokens())->toBe(5);
    });
