<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('ai_chat_conversations', function (
            Blueprint $table
        ) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status')->default('ai');
            $table->uuidMorphs('owner');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_conversations');
    }
};
