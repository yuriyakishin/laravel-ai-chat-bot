<?php

use Yu\AiChatBot\Chat\AiChatService;
use Yu\AiChatBot\Chat\ChatMode;
use Yu\AiChatBot\Chat\RoutingChatService;
use Yu\AiChatBot\Chat\TelegramChatService;
use Yu\AiChatBot\DTO\ChatResult;
use Yu\AiChatBot\Llm\ToolRegistry;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Support\AnonymousOwner;

it('sends every message to the AI in ai mode, even for a conversation in human status', function () {
    config(['ai-chat.mode' => ChatMode::AI->value]);
    Conversation::create(['uuid' => 'conv-1', 'owner_type' => 'guest', 'owner_id' => 'visitor-1', 'status' => 'human']);

    $aiResult = new ChatResult(reply: 'from ai', conversationUuid: 'conv-1', promptTokens: 0, completionTokens: 0);
    $ai = Mockery::mock(AiChatService::class);
    $ai->shouldReceive('send')->once()->andReturn($aiResult);
    $telegram = Mockery::mock(TelegramChatService::class);
    $telegram->shouldNotReceive('send');

    $result = (new RoutingChatService($ai, $telegram))->send(new AnonymousOwner('visitor-1'), 'conv-1', 'hi');

    expect($result->reply())->toBe('from ai');
});

it('does not offer switch_to_human_chat to the LLM in ai mode', function () {
    config(['ai-chat.mode' => ChatMode::AI->value]);

    expect(app(ToolRegistry::class)->has('switch_to_human_chat'))->toBeFalse()
        ->and(app(ToolRegistry::class)->has('current_datetime'))->toBeTrue();
});
