<?php

use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Models\Message;

it('persists a conversation with its messages in order', function () {
    $conversation = Conversation::create([
        'uuid' => 'conv-1',
        'owner_type' => 'App\\Models\\User',
        'owner_id' => 1,
    ]);

    $conversation->messages()->create([
        'role' => 'user',
        'content' =>
            'hi'
    ]);
    $conversation->messages()->create([
        'role' => 'assistant',
        'content' => 'hello!'
    ]);

    $fresh = Conversation::where('uuid', 'conv-1')->first();

    expect($fresh->messages)->toHaveCount(2)
        ->and($fresh->messages->first())->toBeInstanceOf(Message::class)
        ->and($fresh->messages->pluck('role')->all())->toBe([
            'user',
            'assistant'
        ]);
});
