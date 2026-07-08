<?php
// database/migrations/xxxx_create_employees_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('employee_id')->unique();   // e.g. EMP-001
            $table->string('department')->nullable();
            $table->string('phone')->nullable();
            $table->string('qr_token')->unique();      // embedded in QR code
            $table->time('work_start')->default('08:00:00');
            $table->time('work_end')->default('17:00:00');
            $table->decimal('salary', 10, 2)->nullable();  // employee salary
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
