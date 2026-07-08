<?php
// database/migrations/xxxx_create_locations_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // e.g. "Main Office", "Warehouse B"
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7);            // GPS lat  e.g. 11.5564
            $table->decimal('longitude', 10, 7);           // GPS lng  e.g. 104.9282
            $table->integer('radius_meters')->default(100);// allowed radius in meters
            $table->string('qr_token')->unique();          // each location has its own QR
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('locations'); }
};