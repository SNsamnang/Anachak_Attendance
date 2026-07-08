<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('eligible')->default(true); // true = can earn OT
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_statuses');
    }
};
