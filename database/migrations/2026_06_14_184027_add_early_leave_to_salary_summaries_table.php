<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('salary_summaries', function (Blueprint $table) {
            $table->decimal('early_leave', 8, 2)->default(0)->after('late_amount');
            $table->decimal('early_leave_amount', 10, 2)->default(0)->after('early_leave');
        });
    }

    public function down(): void
    {
        Schema::table('salary_summaries', function (Blueprint $table) {
            $table->dropColumn(['early_leave', 'early_leave_amount']);
        });
    }
};
