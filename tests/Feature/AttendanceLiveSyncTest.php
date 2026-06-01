<?php

namespace Tests\Feature;

use App\Models\BiometricAttendance;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AttendanceLiveSyncTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not installed in this environment.');
        }

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('migrate', [
            '--path' => '../biometric-attendance/database/migrations',
            '--force' => true,
        ]);
    }

    public function test_test_attendance_route_inserts_a_live_punch(): void
    {
        $response = $this->get('/test-attendance?serial_number=TEST-123&employee_code=7&punch_state=1&verify_type=2&punch_time=2026-05-28%2018:00:00');

        $response->assertOk()
            ->assertJsonPath('store_counts.inserted', 1)
            ->assertJsonPath('parsed_records.0.employee_code', '7')
            ->assertJsonPath('parsed_records.0.punch_state', 1);

        $this->assertDatabaseHas('biometric_attendances', [
            'employee_code' => '7',
            'punch_time' => '2026-05-28 18:00:00',
            'punch_state' => 1,
            'verify_type' => 2,
        ]);
    }

    public function test_attendance_feed_returns_latest_rows(): void
    {
        BiometricAttendance::query()->create([
            'employee_code' => '9',
            'punch_time' => '2026-05-28 09:10:00',
            'punch_state' => 0,
            'verify_type' => 1,
        ]);

        $response = $this->getJson('/attendance/feed?date=2026-05-28');

        $response->assertOk()
            ->assertJsonPath('summary.total_punches', 1)
            ->assertJsonPath('summary.check_ins', 1)
            ->assertJsonPath('rows.0.employee_code', '9')
            ->assertJsonPath('rows.0.punch_state_label', 'Check-In');
    }
}
