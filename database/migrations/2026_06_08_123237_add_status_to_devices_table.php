<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Status: pending, approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')->after('token');
            // Browser/device fingerprint (user-agent + screen etc.)
            $table->string('fingerprint')->nullable()->after('status');
            // Timestamps for workflow
            $table->timestamp('requested_at')->nullable()->after('fingerprint');
            $table->timestamp('approved_at')->nullable()->after('requested_at');
            $table->string('rejected_reason')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'fingerprint',
                'requested_at',
                'approved_at',
                'rejected_reason'
            ]);
        });
    }
};
