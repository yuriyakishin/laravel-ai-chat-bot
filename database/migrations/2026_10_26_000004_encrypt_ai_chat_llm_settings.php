<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('ai_chat_llm_settings', function (Blueprint $table) {
            $table->text('settings')->change();
        });

        DB::table('ai_chat_llm_settings')->orderBy('id')->each(function (object $row) {
            try {
                Crypt::decryptString($row->settings);
                return;
            } catch (DecryptException) {
            }

            DB::table('ai_chat_llm_settings')
                ->where('id', $row->id)
                ->update(['settings' => Crypt::encryptString($row->settings)]);
        });
    }

    public function down(): void
    {
        DB::table('ai_chat_llm_settings')->orderBy('id')->each(function (object $row) {
            try {
                $settings = Crypt::decryptString($row->settings);
            } catch (DecryptException) {
                return;
            }

            DB::table('ai_chat_llm_settings')->where('id', $row->id)->update(['settings' => $settings]);
        });

        Schema::table('ai_chat_llm_settings', function (Blueprint $table) {
            $table->json('settings')->change();
        });
    }
};
