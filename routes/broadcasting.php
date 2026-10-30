<?php

declare(strict_types=1);

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Yu\AiChatBot\Http\Middleware\IsChatEnabled;
use Yu\AiChatBot\Http\Controllers\BroadcastingAuthController;

Route::middleware([
    IsChatEnabled::class,
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    ValidateCsrfToken::class,
])->prefix(config('ai-chat.route_prefix'))->group(function () {
    Route::post('broadcasting/auth', BroadcastingAuthController::class)->name('ai-chat.broadcasting.auth');
});
