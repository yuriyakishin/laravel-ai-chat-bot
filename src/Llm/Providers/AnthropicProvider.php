<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Providers;

use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\Contracts\LlmProviderInterface;
use Yu\AiChatBot\Contracts\LlmRequestInterface;
use Yu\AiChatBot\Contracts\LlmResponseInterface;
use Yu\AiChatBot\Contracts\LlmSettingsInterface;
use Yu\AiChatBot\DTO\LlmResponse;
use Yu\AiChatBot\DTO\ToolCall;
use Yu\AiChatBot\Exceptions\LlmProviderException;

class AnthropicProvider implements LlmProviderInterface
{
    private const API_VERSION = '2023-06-01';

    /**
     * @param LlmSettingsInterface $settings
     */
    public function __construct(private readonly LlmSettingsInterface $settings)
    {
    }

    /**
     * @param LlmRequestInterface $request
     * @return LlmResponseInterface
     */
    public function send(LlmRequestInterface $request): LlmResponseInterface
    {
        [$system, $messages] = $this->mapMessages($request->messages());

        $payload = [
            'model' => $this->settings->model(),
            'max_tokens' => $this->settings->maxTokens(),
            'temperature' => $this->settings->temperature(),
            'messages' => $messages,
        ];

        if ($system !== null) {
            $payload['system'] = $system;
        }

        if ($request->tools() !== []) {
            $payload['tools'] = $this->mapTools($request->tools());
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->settings->apiKey(),
            'anthropic-version' => self::API_VERSION,
        ])
            ->retry(2, 200, throw: false)
            ->post($this->settings->endpoint(), $payload);

        if ($response->failed()) {
            throw new LlmProviderException(
                "Anthropic request failed with status {$response->status()}: {$response->body()}",
                retryable: $response->serverError(),
            );
        }

        $data = $response->json();
        $content = '';
        $toolCalls = [];

        foreach ($data['content'] as $block) {
            if ($block['type'] === 'text') {
                $content .= $block['text'];
            } elseif ($block['type'] === 'tool_use') {
                $toolCalls[] = new ToolCall(
                    id: $block['id'],
                    name: $block['name'],
                    arguments: $block['input'],
                );
            }
        }

        return new LlmResponse(
            content: $content,
            promptTokens: $data['usage']['input_tokens'],
            completionTokens: $data['usage']['output_tokens'],
            toolCalls: $toolCalls,
        );
    }

    /**
     * @param array $messages
     * @return array{0: string|null, 1: array}
     */
    private function mapMessages(array $messages): array
    {
        $system = null;
        $mapped = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system = $message['content'];
                continue;
            }

            if ($message['role'] === 'tool') {
                $resultBlock = [
                    'type' => 'tool_result',
                    'tool_use_id' => $message['tool_call_id'],
                    'content' => $message['content'],
                ];

                $lastIndex = count($mapped) - 1;
                $last = $mapped[$lastIndex] ?? null;

                if ($last !== null && $this->isToolResultBatch($last)) {
                    $mapped[$lastIndex]['content'][] = $resultBlock;
                } else {
                    $mapped[] = ['role' => 'user', 'content' => [$resultBlock]];
                }

                continue;
            }

            if ($message['role'] === 'assistant' && isset($message['tool_calls'])) {
                $blocks = [];

                if ($message['content'] !== '') {
                    $blocks[] = ['type' => 'text', 'text' => $message['content']];
                }

                foreach ($message['tool_calls'] as $call) {
                    $blocks[] = [
                        'type' => 'tool_use',
                        'id' => $call['id'],
                        'name' => $call['function']['name'],
                        'input' => json_decode($call['function']['arguments'], true),
                    ];
                }

                $mapped[] = ['role' => 'assistant', 'content' => $blocks];
                continue;
            }

            $mapped[] = ['role' => $message['role'], 'content' => $message['content']];
        }

        return [$system, $mapped];
    }

    /**
     * @param array $message
     * @return bool
     */
    private function isToolResultBatch(array $message): bool
    {
        return $message['role'] === 'user'
            && is_array($message['content'])
            && ($message['content'][0]['type'] ?? null) === 'tool_result';
    }

    /**
     * @param array $tools
     * @return array
     */
    private function mapTools(array $tools): array
    {
        return array_map(
            static fn(array $tool): array => [
                'name' => $tool['function']['name'],
                'description' => $tool['function']['description'],
                'input_schema' => $tool['function']['parameters'],
            ],
            $tools
        );
    }
}
