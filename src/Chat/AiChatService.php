<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Chat;

use Yu\AiChatBot\Contracts\LlmProviderInterface;
use Yu\AiChatBot\Contracts\LlmRequestInterface;
use Yu\AiChatBot\Contracts\LlmResponseInterface;
use Yu\AiChatBot\Contracts\ChatServiceInterface;
use Yu\AiChatBot\Contracts\ChatResultInterface;
use Yu\AiChatBot\DTO\ChatResult;
use Yu\AiChatBot\DTO\LlmRequest;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Llm\ToolRegistry;
use Yu\AiChatBot\Contracts\ToolCallInterface;

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

        foreach ($conversation->messages()->whereIn('role', ['user', 'assistant'])->orderBy('id')->get() as $item) {
            $messages[] = ['role' => $item->role, 'content' => $item->content];
        }

        $maxRounds = config('ai-chat.max_tool_rounds');

        for ($round = 1; $round <= $maxRounds; $round++) {
            $tools = $round < $maxRounds ? $this->toolRegistry->definitions() : [];

            $response = $this->provider->send(new LlmRequest($messages, $tools));

            if ($response->toolCalls() === []) {
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
                            'arguments' => json_encode($call->arguments()),
                        ],
                    ],
                    $response->toolCalls()
                ),
            ];

            foreach ($response->toolCalls() as $call) {
                $result = $this->toolRegistry->get($call->name())->execute($call->arguments());

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

        $conversation->messages()->create(['role' => 'assistant', 'content' => $response->content()]);

        $result = new ChatResult(
            reply: $response->content(),
            conversationUuid: $conversationUuid,
            promptTokens: $response->promptTokens(),
            completionTokens: $response->completionTokens(),
        );

        return $result;
    }
}
