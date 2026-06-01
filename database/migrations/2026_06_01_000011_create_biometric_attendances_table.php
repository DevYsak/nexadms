<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->index();
            $table->foreignId('device_id')->nullable()->constrained('biometric_devices')->nullOnDelete();
            $table->dateTime('punch_time')->index();
            $table->string('event_type')->default('unknown');
            $table->tinyInteger('verify_type')->default(0);
            $table->unsignedBigInteger('session_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_attendances');
    }
};
