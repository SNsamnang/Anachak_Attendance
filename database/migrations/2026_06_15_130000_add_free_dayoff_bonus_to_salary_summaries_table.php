<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_summaries', function (Blueprint $table) {
            $table->unsignedTinyInteger('unused_free_days')->default(0)->after('ot_amount');
            $table->decimal('free_dayoff_bonus', 10, 2)->default(0)->after('unused_free_days');
        });
    }

    public function down(): void
    {
        Schema::table('salary_summaries', function (Blueprint $table) {
            $table->dropColumn(['unused_free_days', 'free_dayoff_bonus']);
        });
    }
};
