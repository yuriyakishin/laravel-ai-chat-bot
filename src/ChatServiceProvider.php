<?php

declare(strict_types=1);

namespace Yu\AiChatBot;

use Illuminate\Support\ServiceProvider;
use Yu\AiChatBot\Contracts\LlmProviderInterface;
use Yu\AiChatBot\Contracts\LlmSettingsInterface;
use Yu\AiChatBot\Contracts\ChatServiceInterface;
use Yu\AiChatBot\Chat\RoutingChatService;
use Yu\AiChatBot\Llm\ToolRegistry;
use Yu\AiChatBot\Chat\CurrentConversation;
use Yu\AiChatBot\Telegram\TelegramClient;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-chat.php', 'ai-chat');

        $llm = config('ai-chat.default');
        $config = config('ai-chat.llm.' . $llm);

        $this->app->singleton(LlmProviderInterface::class, $config['provider_class']);
        $this->app->singleton(LlmSettingsInterface::class, $config['settings_class']);
        $this->app->singleton(ChatServiceInterface::class, RoutingChatService::class);
        $this->app->singleton(ToolRegistry::class, fn(): ToolRegistry => $this->buildToolRegistry());
        $this->app->singleton(CurrentConversation::class);
        $this->app->singleton(
            TelegramClient::class,
            fn() => new TelegramClient(
                config('ai-chat.telegram.bot_token'),
                config('ai-chat.telegram.admin_chat_id'),)
        );
    }

    /**
     * @return ToolRegistry
     */
    private function buildToolRegistry(): ToolRegistry
    {
        $registry = new ToolRegistry();

        foreach (config('ai-chat.tools') as $toolClass) {
            $registry->register($this->app->make($toolClass));
        }

        return $registry;
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([__DIR__ . '/../config/ai-chat.php' => config_path('ai-chat.php'),], 'ai-chat-config');
        $this->publishes([__DIR__ . '/../resources/dist' => public_path('vendor/ai-chat'),], 'ai-chat-assets');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ai-chat');
    }
}
