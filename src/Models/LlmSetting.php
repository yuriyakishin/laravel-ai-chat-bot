<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Models;

use Illuminate\Database\Eloquent\Model;

class LlmSetting extends Model
{
    protected $table = 'ai_chat_llm_settings';

    protected $fillable = ['provider', 'settings'];

    protected $casts = ['settings' => 'array'];
}
