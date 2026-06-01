<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_raw_logs', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->index();
            $table->string('log_type')->index();
            $table->longText('body')->nullable();
            $table->integer('records_parsed')->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_raw_logs');
    }
};
