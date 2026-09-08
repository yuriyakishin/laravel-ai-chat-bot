<?php

use Yu\AiChatBot\DTO\LlmRequest;

it('exposes its constructor values through the interface methods',
    function () {
        $request = new LlmRequest(
            messages: [['role' => 'user', 'content' => 'hi']]
        );

        expect($request->messages())->toBe([
            [
                'role' => 'user',
                'content' => 'hi'
            ]
        ]);
    });
