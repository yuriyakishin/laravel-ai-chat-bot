<?php

use Yu\AiChatBot\Models\Conversation;

it('returns only messages newer than the after parameter', function () {
    $conversation = Conversation::create(['uuid' => 'conv-1', 'owner_type' => 'guest', 'owner_id' => 'visitor-1']);
    $first = $conversation->messages()->create(['role' => 'user', 'content' => 'hi']);
    $conversation->messages()->create(['role' => 'admin', 'content' => 'hello from admin']);

    $all = $this->withCredentials()->withCookie('ai_chat_visitor_id', 'visitor-1')->getJson(route('ai-chat.messages'));
    $newer = $this->withCredentials()->withCookie('ai_chat_visitor_id', 'visitor-1')
        ->getJson(route('ai-chat.messages', ['after' => $first->id]));

    expect($all->json('messages'))->toHaveCount(2)
        ->and($newer->json('messages'))->toHaveCount(1)
        ->and($newer->json('messages.0.content'))->toBe('hello from admin');
});
