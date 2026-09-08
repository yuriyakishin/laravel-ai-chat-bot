<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Llm\Tools;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Yu\AiChatBot\Contracts\ToolInterface;
use Yu\AiChatBot\Chat\CurrentConversation;
use Yu\AiChatBot\Telegram\TelegramClient;
use Yu\AiChatBot\Models\Conversation;
use Yu\AiChatBot\Models\TelegramMessage;

class SwitchToHumanChatTool implements ToolInterface
{
    /**
     * @param CurrentConversation $currentConversation
     * @param TelegramClient $telegramClient
     */
    public function __construct(
        private CurrentConversation $currentConversation,
        private TelegramClient $telegramClient
    ) {
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return 'switch_to_human_chat';
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return 'Connects the customer with a human administrator. Call this only after the customer has explicitly confirmed they want to talk to a human - not just because you are unsure of an answer. '
            . 'If the result status is "handoff_failed", apologize and tell the customer that no operators are currently available - do not call this tool again in the same conversation.';
    }

    /**
     * @return array
     */
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reason' => [
                    'type' => 'string',
                    'description' => 'A short summary of what the customer needs, shown to the admin.',
                ],
            ],
            'required' => [],
        ];
    }

    /**
     * @param array $arguments
     * @return array
     */
    public function execute(array $arguments): array
    {
        $conversation = Conversation::where('uuid', $this->currentConversation->get())->firstOrFail();

        try {
            $messageId = $this->telegramClient
                ->sendMessage("New request — conversation #{$conversation->uuid}\n\n" . ($arguments['reason'] ?? ''));
        } catch (ConnectionException|RequestException $e) {
            return ['status' => 'handoff_failed'];
        }

        $conversation->update(['status' => 'human']);

        TelegramMessage::create([
            'conversation_id' => $conversation->id,
            'telegram_message_id' => $messageId,
        ]);

        return ['status' => 'handoff_requested'];
    }
}
