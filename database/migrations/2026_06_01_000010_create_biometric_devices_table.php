<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('name')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('pushver')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
};
