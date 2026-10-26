<?php

declare(strict_types=1);

namespace Yu\AiChatBot;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Yu\AiChatBot\Contracts\LlmProviderInterface;
use Yu\AiChatBot\Contracts\LlmSettingsInterface;
use Yu\AiChatBot\Contracts\ChatServiceInterface;
use Yu\AiChatBot\Chat\ChatMode;
use Yu\AiChatBot\Chat\RoutingChatService;
use Yu\AiChatBot\Llm\Tools\SwitchToHumanChatTool;
use Yu\AiChatBot\Llm\ToolRegistry;
use Yu\AiChatBot\Chat\CurrentConversation;
use Yu\AiChatBot\Telegram\TelegramClient;
use Yu\AiChatBot\Events\ConversationEscalatedToHumanEvent;
use Yu\AiChatBot\Listeners\LogConversationEscalationListener;
use Yu\AiChatBot\Console\Commands\PruneConversationsCommand;

class ChatServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ai-chat.php', 'ai-chat');

        $this->app->singleton(
            LlmProviderInterface::class,
            fn($app) => $app->make($this->llmConfig()['provider_class'])
        );
        $this->app->singleton(
            LlmSettingsInterface::class,
            fn($app) => $app->make($this->llmConfig()['settings_class'])
        );
        $this->app->singleton(ChatServiceInterface::class, RoutingChatService::class);
        $this->app->singleton(ToolRegistry::class, fn(): ToolRegistry => $this->buildToolRegistry());
        $this->app->singleton(CurrentConversation::class);
        $this->app->singleton(
            TelegramClient::class,
            fn() => new TelegramClient(
                config('ai-chat.telegram.bot_token'),
                config('ai-chat.telegram.admin_chat_id'),)
        );

        if (!config()->has('logging.channels.ai-chat')) {
            config([
                'logging.channels.ai-chat' => [
                    'driver' => 'single',
                    'path' => storage_path('logs/ai-chat.log'),
                ]
            ]);
        }
    }

    /**
     * @return array
     */
    private function llmConfig(): array
    {
        $llm = config('ai-chat.default');
        $config = config('ai-chat.llm.' . $llm);

        if (!is_array($config) || !isset($config['provider_class'], $config['settings_class'])) {
            throw new InvalidArgumentException(
                "Unknown AI chat LLM provider [{$llm}]. Set AI_CHAT_LLM_DEFAULT to one of: "
                . implode(', ', array_keys(config('ai-chat.llm', [])))
            );
        }

        return $config;
    }

    /**
     * @return ToolRegistry
     */
    private function buildToolRegistry(): ToolRegistry
    {
        $registry = new ToolRegistry();

        foreach (config('ai-chat.tools') as $toolClass) {
            if ($toolClass === SwitchToHumanChatTool::class && config('ai-chat.mode') === ChatMode::AI->value) {
                continue;
            }

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
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'ai-chat');

        /** register listeners */
        Event::listen(
            ConversationEscalatedToHumanEvent::class,
            LogConversationEscalationListener::class
        );

        /** register ai-chat commands */
        if ($this->app->runningInConsole()) {
            $this->commands([PruneConversationsCommand::class]);
        }

        /** register schedule */
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            if (config('ai-chat.prune_after_days') > 0) {
                $schedule->command('ai-chat:prune')->daily();
            }
        });

        /** register limiters */
        RateLimiter::for('ai-chat-widget', function ($request) {
            return Limit::perMinute(config('ai-chat.rate_limit_per_minute'))->by($request->ip());
        });
    }
}
