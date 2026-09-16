<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessage extends Model
{
    protected $table = 'ai_chat_telegram_messages';

    protected $fillable = ['conversation_id', 'telegram_message_id'];

    /**
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
