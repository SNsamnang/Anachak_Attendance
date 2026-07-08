<?php
// database/migrations/xxxx_create_attendances_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['in', 'out']);
            $table->decimal('scanned_lat', 10, 7)->nullable();   // where employee actually was
            $table->decimal('scanned_lng', 10, 7)->nullable();
            $table->decimal('distance_meters', 8, 2)->nullable(); // distance from location center
            $table->boolean('location_verified')->default(false); // was inside radius?
            $table->timestamp('scanned_at');
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
