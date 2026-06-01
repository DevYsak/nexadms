<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code')->index();
            $table->date('session_date')->index();
            $table->unsignedTinyInteger('session_index')->default(0);
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('status')->default('absent');
            $table->boolean('is_overnight')->default(false);
            $table->text('admin_note')->nullable();
            $table->string('corrected_by')->nullable();
            $table->timestamp('corrected_at')->nullable();
            $table->timestamps();

            $table->index(['employee_code', 'session_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};
