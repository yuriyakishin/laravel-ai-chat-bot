<?php

use Yu\AiChatBot\Chat\AiChatService;
use Yu\AiChatBot\Chat\CurrentConversation;
use Yu\AiChatBot\Contracts\LlmProviderInterface;
use Yu\AiChatBot\Contracts\LlmRequestInterface;
use Yu\AiChatBot\Contracts\LlmResponseInterface;
use Yu\AiChatBot\Contracts\ToolInterface;
use Yu\AiChatBot\DTO\LlmResponse;
use Yu\AiChatBot\DTO\ToolCall;
use Yu\AiChatBot\Llm\ToolRegistry;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Support\AnonymousOwner;

/**
 * @param array<int, LlmResponseInterface> $responses
 */
function fakeLlmProvider(array $responses): LlmProviderInterface
{
    return new class($responses) implements LlmProviderInterface {
        /** @var array<int, LlmRequestInterface> */
        public array $requests = [];

        public function __construct(private array $responses)
        {
        }

        public function send(LlmRequestInterface $request): LlmResponseInterface
        {
            $this->requests[] = $request;

            return array_shift($this->responses);
        }
    };
}

function countingTool(): ToolInterface
{
    return new class implements ToolInterface {
        public int $calls = 0;

        public function name(): string
        {
            return 'lookup';
        }

        public function description(): string
        {
            return 'Looks something up';
        }

        public function parameters(): array
        {
            return ['type' => 'object', 'properties' => new stdClass()];
        }

        public function execute(array $arguments): array
        {
            $this->calls++;

            return ['found' => true];
        }
    };
}

function toolCallResponse(string $id): LlmResponse
{
    return new LlmResponse(content: '', promptTokens: 1, completionTokens: 1,
        toolCalls: [new ToolCall(id: $id, name: 'lookup', arguments: [])]);
}

function textResponse(string $content): LlmResponse
{
    return new LlmResponse(content: $content, promptTokens: 1, completionTokens: 1);
}

function aiChatService(LlmProviderInterface $provider, ?ToolInterface $tool = null): AiChatService
{
    $registry = new ToolRegistry();

    if ($tool !== null) {
        $registry->register($tool);
    }

    return new AiChatService($provider, $registry, new CurrentConversation());
}

beforeEach(function () {
    config(['ai-chat.system_prompt' => '']);
});

it('forbids tool calls on the last round and never executes tools returned there', function () {
    config(['ai-chat.max_tool_rounds' => 2]);

    $provider = fakeLlmProvider([toolCallResponse('call_1'), toolCallResponse('call_2')]);
    $tool = countingTool();

    aiChatService($provider, $tool)->send(new AnonymousOwner('visitor-1'), 'conv-1', 'hi');

    expect($provider->requests)->toHaveCount(2)
        ->and($provider->requests[0]->allowToolCalls())->toBeTrue()
        ->and($provider->requests[1]->allowToolCalls())->toBeFalse()
        ->and($provider->requests[1]->tools())->not->toBe([])
        ->and($tool->calls)->toBe(1);
});

it('treats max_tool_rounds below one as a single round', function () {
    config(['ai-chat.max_tool_rounds' => 0]);

    $provider = fakeLlmProvider([textResponse('Hello!')]);

    $result = aiChatService($provider)->send(new AnonymousOwner('visitor-1'), 'conv-1', 'hi');

    expect($result->reply())->toBe('Hello!')
        ->and($provider->requests)->toHaveCount(1);
});

it('does not store an empty assistant reply', function () {
    $provider = fakeLlmProvider([textResponse('')]);

    aiChatService($provider)->send(new AnonymousOwner('visitor-1'), 'conv-1', 'hi');

    $roles = Conversation::where('uuid', 'conv-1')->first()->messages()->pluck('role')->all();
    expect($roles)->toBe(['user']);
});

it('sends only the latest history_limit messages, skips empty ones and starts with a user message', function () {
    config(['ai-chat.history_limit' => 3]);

    $conversation = Conversation::create(['uuid' => 'conv-1', 'owner_type' => 'guest', 'owner_id' => 'visitor-1']);
    $conversation->messages()->create(['role' => 'user', 'content' => 'first question']);
    $conversation->messages()->create(['role' => 'assistant', 'content' => 'first answer']);
    $conversation->messages()->create(['role' => 'assistant', 'content' => '']);
    $conversation->messages()->create(['role' => 'user', 'content' => 'second question']);
    $conversation->messages()->create(['role' => 'assistant', 'content' => 'second answer']);

    $provider = fakeLlmProvider([textResponse('third answer')]);

    aiChatService($provider)->send(new AnonymousOwner('visitor-1'), 'conv-1', 'third question');

    expect($provider->requests[0]->messages())->toBe([
        ['role' => 'user', 'content' => 'second question'],
        ['role' => 'assistant', 'content' => 'second answer'],
        ['role' => 'user', 'content' => 'third question'],
    ]);
});
