<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('telegram_bot_token')->nullable()->after('is_active');
            $table->string('telegram_chat_id')->nullable()->after('telegram_bot_token');
            $table->string('telegram_topic_attendance')->nullable()->after('telegram_chat_id');
            $table->string('telegram_topic_leave')->nullable()->after('telegram_topic_attendance');
            $table->string('telegram_topic_device')->nullable()->after('telegram_topic_leave');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_bot_token',
                'telegram_chat_id',
                'telegram_topic_attendance',
                'telegram_topic_leave',
                'telegram_topic_device',
            ]);
        });
    }
};
