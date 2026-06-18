<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            if (! $this->indexExists('attendance_sessions', 'attendance_sessions_status_index')) {
                $table->index('status');
            }
            if (! $this->indexExists('attendance_sessions', 'attendance_sessions_is_overnight_check_out_at_index')) {
                $table->index(['is_overnight', 'check_out_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropIndexIfExists('attendance_sessions_status_index');
            $table->dropIndexIfExists('attendance_sessions_is_overnight_check_out_at_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(\Illuminate\Support\Facades\DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->contains($index);
    }
};
