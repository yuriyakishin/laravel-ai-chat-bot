<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Providers;

use Illuminate\Support\Facades\Http;
use Yu\AiChatBot\Contracts\LlmProviderInterface;
use Yu\AiChatBot\Contracts\LlmRequestInterface;
use Yu\AiChatBot\Contracts\LlmResponseInterface;
use Yu\AiChatBot\Contracts\LlmSettingsInterface;
use Yu\AiChatBot\DTO\LlmResponse;
use Yu\AiChatBot\Exceptions\LlmProviderException;
use Yu\AiChatBot\DTO\ToolCall;

class OpenAiProvider implements LlmProviderInterface
{
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
        $payload = [
            'model' => $this->settings->model(),
            'messages' => $request->messages(),
            'temperature' => $this->settings->temperature(),
            'max_tokens' => $this->settings->maxTokens(),
        ];

        if ($request->tools() !== []) {
            $payload['tools'] = $request->tools();
        }

        $response = Http::withToken($this->settings->apiKey())
            ->retry(2, 200)
            ->post($this->settings->endpoint(), $payload);

        if ($response->failed()) {
            throw new LlmProviderException(
                "OpenAI request failed with status {$response->status()}: {$response->body()}",
                retryable: $response->serverError(),
            );
        }

        $data = $response->json();
        $message = $data['choices'][0]['message'];

        $toolCalls = array_map(
            static fn(array $call): ToolCall => new ToolCall(
                id: $call['id'],
                name: $call['function']['name'],
                arguments: json_decode($call['function']['arguments'], true),
            ),
            $message['tool_calls'] ?? []
        );

        return new LlmResponse(
            content: $message['content'] ?? '',
            promptTokens: $data['usage']['prompt_tokens'],
            completionTokens: $data['usage']['completion_tokens'],
            toolCalls: $toolCalls,
        );
    }
}
