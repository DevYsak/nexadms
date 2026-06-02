<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->unsignedBigInteger('last_stamp')->default(0)->after('last_activity_at');
            $table->timestamp('last_online_at')->nullable()->after('last_stamp');
            $table->timestamp('went_offline_at')->nullable()->after('last_online_at');
        });
    }

    public function down(): void
    {
        Schema::table('biometric_devices', function (Blueprint $table) {
            $table->dropColumn(['last_stamp', 'last_online_at', 'went_offline_at']);
        });
    }
};
