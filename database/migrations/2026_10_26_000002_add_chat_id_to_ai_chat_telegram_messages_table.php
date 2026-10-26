<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('ai_chat_telegram_messages', function (Blueprint $table) {
            $table->bigInteger('chat_id')->nullable()->after('conversation_id');
            $table->index(['telegram_message_id', 'chat_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_telegram_messages', function (Blueprint $table) {
            $table->dropIndex(['telegram_message_id', 'chat_id']);
            $table->dropColumn('chat_id');
        });
    }
};
