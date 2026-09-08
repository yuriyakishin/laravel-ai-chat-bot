<?php

use Yu\AiChatBot\Llm\Providers\AnthropicProvider;
use Yu\AiChatBot\Llm\Providers\OpenAiProvider;
use Yu\AiChatBot\Llm\Settings\AnthropicSettings;
use Yu\AiChatBot\Llm\Settings\OpenAiSettings;
use Yu\AiChatBot\Llm\Tools\CurrentDateTimeTool;
use Yu\AiChatBot\Llm\Tools\SwitchToHumanChatTool;

return [
    'enabled' => (bool)env('AI_CHAT_ENABLED', true),
    'default' => env('AI_CHAT_LLM_DEFAULT', 'open_ai'),
    'widget_theme' => env('AI_CHAT_WIDGET_THEME', 'default'),
    'route_prefix' => env('AI_CHAT_ROUTE_PREFIX', 'ai-chat'),
    'max_tool_rounds' => (int)env('AI_CHAT_MAX_TOOL_ROUNDS', 3),
    'welcome_message' => env('AI_CHAT_WELCOME_MESSAGE'),
    'system_prompt' => env('AI_CHAT_SYSTEM_PROMPT', "You are a customer support assistant. You may only use the information available through your tools or explicitly given in this conversation - never use your own general knowledge to answer. If you cannot find the answer this way, offer to connect the customer with a human administrator."),
    'telegram' => [
        'bot_token' => env('AI_CHAT_TELEGRAM_BOT_TOKEN', ''),
        'admin_chat_id' => env('AI_CHAT_TELEGRAM_ADMIN_CHAT_ID', ''),
        'webhook_secret' => env('AI_CHAT_TELEGRAM_WEBHOOK_SECRET', ''),
    ],

    'tools' => [
        CurrentDateTimeTool::class,
        SwitchToHumanChatTool::class,
    ],

    'llm' => [
        'open_ai' => [
            'provider_class' => env('AI_CHAT_LLM_OPEN_AI_PROVIDER_CLASS', OpenAiProvider::class),
            'settings_class' => env('AI_CHAT_LLM_OPEN_AI_SETTINGS_CLASS', OpenAiSettings::class),
            'endpoint' => env('AI_CHAT_LLM_OPEN_AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
            'api_key' => env('AI_CHAT_LLM_OPEN_AI_API_KEY', ''),
            'model' => env('AI_CHAT_LLM_OPEN_AI_MODEL', 'gpt-4o-mini'),
            'temperature' => env('AI_CHAT_LLM_OPEN_AI_TEMPERATURE', 0.7),
            'max_tokens' => env('AI_CHAT_LLM_OPEN_AI_MAX_TOKENS', 5000),
        ],
        'anthropic' => [
            'provider_class' => env('AI_CHAT_LLM_ANTHROPIC_PROVIDER_CLASS', AnthropicProvider::class),
            'settings_class' => env('AI_CHAT_LLM_ANTHROPIC_SETTINGS_CLASS', AnthropicSettings::class),
            'endpoint' => env('AI_CHAT_LLM_ANTHROPIC_ENDPOINT', 'https://api.anthropic.com/v1/messages'),
            'api_key' => env('AI_CHAT_LLM_ANTHROPIC_API_KEY', ''),
            'model' => env('AI_CHAT_LLM_ANTHROPIC_MODEL', 'claude-sonnet-5'),
            'temperature' => env('AI_CHAT_LLM_ANTHROPIC_TEMPERATURE', 0.7),
            'max_tokens' => env('AI_CHAT_LLM_ANTHROPIC_MAX_TOKENS', 5000),
        ],
    ],
];
