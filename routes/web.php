<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Yu\AiChatBot\Http\Middleware\IsChatEnabled;
use Yu\AiChatBot\Http\Controllers\WidgetController;
use Yu\AiChatBot\Http\Controllers\TelegramWebhookController;

Route::middleware([
    IsChatEnabled::class,
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
])->prefix(config('ai-chat.route_prefix'))->group(function () {
    Route::get('messages', [WidgetController::class, 'messages'])->name('ai-chat.messages');
    Route::post('send', [WidgetController::class, 'send'])->name('ai-chat.send');
    Route::post('reset', [WidgetController::class, 'reset'])->name('ai-chat.reset');
});

Route::prefix(config('ai-chat.route_prefix'))->group(function () {
    Route::post('telegram-webhook', [
        TelegramWebhookController::class,
        'handle'
    ])->name('ai-chat.telegram-webhook');
});

