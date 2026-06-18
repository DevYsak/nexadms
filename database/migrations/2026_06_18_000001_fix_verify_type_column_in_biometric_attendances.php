<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biometric_attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('verify_type')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('biometric_attendances', function (Blueprint $table) {
            $table->tinyInteger('verify_type')->default(0)->change();
        });
    }
};
