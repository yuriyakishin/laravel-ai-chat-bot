<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('ai_chat_conversations', function (Blueprint $table) {
            $table->string('owner_id', 36)->change();
        });
    }

    public function down(): void
    {
        // Intentionally left empty: integer owner ids stored after up() cannot be converted back to uuid.
    }
};
