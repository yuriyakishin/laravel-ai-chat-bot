<?php

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Yu\AiChatBot\Exceptions\LlmProviderException;
use Yu\AiChatBot\Llm\Settings\OpenAiDatabaseSettings;
use Yu\AiChatBot\Models\LlmSetting;

it('stores the settings encrypted and reads them back', function () {
    LlmSetting::create(['provider' => 'open_ai', 'settings' => ['api_key' => 'sk-secret', 'model' => 'gpt-4o-mini']]);

    $raw = DB::table('ai_chat_llm_settings')->value('settings');

    expect($raw)->not->toContain('sk-secret')
        ->and((new OpenAiDatabaseSettings())->apiKey())->toBe('sk-secret');
});

it('throws a configuration error when the open_ai row is missing', function () {
    expect(fn() => (new OpenAiDatabaseSettings())->apiKey())
        ->toThrow(LlmProviderException::class, 'OpenAI settings are not configured');
});

it('encrypts settings rows that were stored as plain JSON before the upgrade', function () {
    DB::table('ai_chat_llm_settings')->insert([
        'provider' => 'open_ai',
        'settings' => json_encode(['api_key' => 'sk-plain']),
    ]);

    $migration = require __DIR__ . '/../../../database/migrations/2026_10_26_000004_encrypt_ai_chat_llm_settings.php';
    $migration->up();

    $raw = DB::table('ai_chat_llm_settings')->value('settings');

    expect($raw)->not->toContain('sk-plain')
        ->and(json_decode(Crypt::decryptString($raw), true))->toBe(['api_key' => 'sk-plain'])
        ->and(LlmSetting::first()->settings)->toBe(['api_key' => 'sk-plain']);
});
