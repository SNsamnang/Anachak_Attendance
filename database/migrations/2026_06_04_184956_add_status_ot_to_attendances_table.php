<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // 'on_time' or 'late' — set when type = 'in'
            $table->enum('check_in_status', ['on_time', 'late'])->nullable()->after('type');
            // 'on_time' or 'early' — set when type = 'out'
            $table->enum('check_out_status', ['on_time', 'early'])->nullable()->after('check_in_status');
            // Overtime duration in seconds (calculated on check-out)
            $table->unsignedInteger('ot_seconds')->default(0)->after('check_out_status');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_status', 'check_out_status', 'ot_seconds']);
        });
    }
};
