<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_summaries', function (Blueprint $table) {
            $table->integer('leave_day')->default(0)->after('dayoff_amount');
            $table->decimal('leave_day_amount', 10, 2)->default(0)->after('leave_day');
        });
    }

    public function down(): void
    {
        Schema::table('salary_summaries', function (Blueprint $table) {
            $table->dropColumn(['leave_day', 'leave_day_amount']);
        });
    }
};
