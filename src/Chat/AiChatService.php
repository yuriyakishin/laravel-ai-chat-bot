<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Chat;

use Yu\AiChatBot\Contracts\LlmProviderInterface;
use Yu\AiChatBot\Contracts\ChatServiceInterface;
use Yu\AiChatBot\Contracts\ChatResultInterface;
use Yu\AiChatBot\DTO\ChatResult;
use Yu\AiChatBot\DTO\LlmRequest;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Llm\ToolRegistry;
use Yu\AiChatBot\Contracts\ToolCallInterface;
use Yu\AiChatBot\Events\LlmResponseReceivedEvent;

class AiChatService implements ChatServiceInterface
{
    /**
     * @param LlmProviderInterface $provider
     * @param ToolRegistry $toolRegistry
     * @param CurrentConversation $currentConversation
     */
    public function __construct(
        private LlmProviderInterface $provider,
        private ToolRegistry $toolRegistry,
        private CurrentConversation $currentConversation
    ) {
    }

    /**
     * @param mixed $owner
     * @param string $conversationUuid
     * @param string $message
     * @return ChatResultInterface
     */
    public function send(mixed $owner, string $conversationUuid, string $message): ChatResultInterface
    {
        $this->currentConversation->set($conversationUuid);

        $conversation = Conversation::query()->firstOrCreate(
            ['uuid' => $conversationUuid],
            ['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->getKey()]
        );

        $conversation->messages()->create(['role' => 'user', 'content' => $message]);
        $messages = [];
        $systemPrompt = config('ai-chat.system_prompt');

        if ($systemPrompt !== null && $systemPrompt !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $history = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->where('content', '!=', '')
            ->latest('id')
            ->limit(max(1, (int)config('ai-chat.history_limit', 30)))
            ->get()
            ->reverse()
            ->skipUntil(static fn($item): bool => $item->role === 'user');

        foreach ($history as $item) {
            $messages[] = ['role' => $item->role, 'content' => $item->content];
        }

        $maxRounds = max(1, (int)config('ai-chat.max_tool_rounds'));
        $tools = $this->toolRegistry->definitions();

        for ($round = 1; $round <= $maxRounds; $round++) {
            $response = $this->provider->send(new LlmRequest($messages, $tools, $round < $maxRounds));

            event(new LlmResponseReceivedEvent($response, $conversationUuid));

            if ($response->toolCalls() === [] || $round === $maxRounds) {
                break;
            }

            $messages[] = [
                'role' => 'assistant',
                'content' => $response->content(),
                'tool_calls' => array_map(
                    static fn(ToolCallInterface $call): array => [
                        'id' => $call->id(),
                        'type' => 'function',
                        'function' => [
                            'name' => $call->name(),
                            'arguments' => json_encode((object)$call->arguments()),
                        ],
                    ],
                    $response->toolCalls()
                ),
            ];

            foreach ($response->toolCalls() as $call) {
                $result = $this->toolRegistry->has($call->name())
                    ? $this->toolRegistry->get($call->name())->execute($call->arguments())
                    : ['error' => "Unknown tool: {$call->name()}"];

                $conversation->messages()->create([
                    'role' => 'tool',
                    'content' => json_encode([
                        'name' => $call->name(),
                        'arguments' => $call->arguments(),
                        'result' => $result
                    ]),
                ]);

                $messages[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call->id(),
                    'content' => json_encode($result),
                ];
            }
        }

        if ($response->content() !== '') {
            $conversation->messages()->create(['role' => 'assistant', 'content' => $response->content()]);
        }

        $result = new ChatResult(
            reply: $response->content(),
            conversationUuid: $conversationUuid,
            promptTokens: $response->promptTokens(),
            completionTokens: $response->completionTokens(),
        );

        return $result;
    }
}
