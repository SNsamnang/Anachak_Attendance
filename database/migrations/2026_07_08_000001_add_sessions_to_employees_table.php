<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedTinyInteger('sessions')->default(1)->after('work_end');
            $table->time('session2_start')->nullable()->after('sessions');
            $table->time('session2_end')->nullable()->after('session2_start');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['sessions', 'session2_start', 'session2_end']);
        });
    }
};
