<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    private const TABLES = ['ai_chat_messages', 'ai_chat_telegram_messages'];

    public function up(): void
    {
        foreach (self::TABLES as $tableName) {
            // MySQL already indexes foreign key columns; PostgreSQL and SQLite do not.
            if (Schema::hasIndex($tableName, ['conversation_id'])) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->index('conversation_id');
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $tableName) {
            $index = "{$tableName}_conversation_id_index";

            if (!Schema::hasIndex($tableName, $index)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($index) {
                $table->dropIndex($index);
            });
        }
    }
};
