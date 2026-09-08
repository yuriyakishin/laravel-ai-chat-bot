<?php

declare(strict_types=1);

namespace Yu\AiChatBot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'ai_chat_messages';

    protected $fillable = ['conversation_id', 'role', 'content'];

    /**
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
