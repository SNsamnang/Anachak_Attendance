<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date_of_record');
            $table->integer('dayoff')->default(0);  // 1 if not checked in, 0 otherwise
            $table->decimal('dayoff_amount', 10, 2)->default(0);  // salary/26
            $table->decimal('late', 8, 2)->default(0);  // hours late
            $table->decimal('late_amount', 10, 2)->default(0);  // (salary/26/8)*late
            $table->decimal('ot', 8, 2)->default(0);  // hours overtime
            $table->decimal('ot_amount', 10, 2)->default(0);  // (salary/26/8)*ot
            $table->timestamps();
            $table->unique(['employee_id', 'date_of_record']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_summaries');
    }
};
