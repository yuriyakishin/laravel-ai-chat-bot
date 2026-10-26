<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;
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

            if (!$request->allowToolCalls()) {
                $payload['tool_choice'] = 'none';
            }
        }

        try {
            $response = Http::withToken($this->settings->apiKey())
                ->timeout((int)config('ai-chat.llm_timeout', 60))
                ->retry(
                    2,
                    200,
                    static fn(Throwable $e): bool => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false
                )
                ->post($this->settings->endpoint(), $payload);
        } catch (ConnectionException $e) {
            throw new LlmProviderException("OpenAI request failed: {$e->getMessage()}", retryable: true);
        }

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
                arguments: is_array($arguments = json_decode($call['function']['arguments'] ?? '', true))
                    ? $arguments
                    : [],
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
