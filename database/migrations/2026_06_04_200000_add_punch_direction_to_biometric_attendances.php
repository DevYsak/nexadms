<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds punch_direction — the raw InOut value sent by the biometric device.
 *
 * Values:
 *   'in'      — device explicitly marked this as a check-in  (InOut = 0, 4)
 *   'out'     — device explicitly marked this as a check-out (InOut = 1, 5)
 *   'unknown' — device sent no direction (old firmware / missing field)
 *
 * Separating device intent from computed event_type means:
 *   - event_type can still be overwritten by the recalculation service (skipped, etc.)
 *   - punch_direction remains the immutable ground truth from the device
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('biometric_attendances', 'punch_direction')) {
            Schema::table('biometric_attendances', function (Blueprint $table) {
                $table->string('punch_direction', 10)->default('unknown')->after('event_type');
                $table->index('punch_direction');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('biometric_attendances', 'punch_direction')) {
            Schema::table('biometric_attendances', function (Blueprint $table) {
                $table->dropIndex(['punch_direction']);
                $table->dropColumn('punch_direction');
            });
        }
    }
};
