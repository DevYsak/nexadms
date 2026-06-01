<?php

namespace Tests\Unit;

use App\Services\AdmsParserService;
use Tests\TestCase;

class AdmsParserServiceTest extends TestCase
{
    public function test_it_parses_plain_tab_attlog_line(): void
    {
        $service = new AdmsParserService();
        $parsed = $service->parseAttLog("1\t2026-05-28 09:00:00\t0\t1\t0\t0");

        $this->assertCount(1, $parsed['records']);
        $this->assertSame('1', $parsed['records'][0]['employee_code']);
        $this->assertSame('2026-05-28 09:00:00', $parsed['records'][0]['punch_time']);
        $this->assertSame(0, $parsed['records'][0]['punch_state']);
        $this->assertSame(1, $parsed['records'][0]['verify_type']);
    }

    public function test_it_parses_attlog_prefix_lines(): void
    {
        $service = new AdmsParserService();
        $parsed = $service->parseAttLog(
            "ATTLOG\t1\t2026-05-28 09:00:00\t0\t1\t0\t0\r\n".
            "ATTLOG\t1\t2026-05-28 18:00:00\t1\t2\t0\t0\r\n"
        );

        $this->assertCount(2, $parsed['records']);
        $this->assertSame(1, $parsed['records'][1]['punch_state']);
        $this->assertSame(2, $parsed['records'][1]['verify_type']);
    }

    public function test_it_parses_comma_separated_attlog_lines(): void
    {
        $service = new AdmsParserService();
        $parsed = $service->parseAttLog("1,2026-05-28 09:00:00,0,1,0,0");

        $this->assertCount(1, $parsed['records']);
        $this->assertSame('1', $parsed['records'][0]['employee_code']);
        $this->assertSame(1, $parsed['records'][0]['verify_type']);
    }

    public function test_it_parses_key_value_attlog_lines(): void
    {
        $service = new AdmsParserService();
        $parsed = $service->parseAttLog("PIN=1\tDateTime=2026-05-28 09:00:00\tStatus=0\tVerify=10");

        $this->assertCount(1, $parsed['records']);
        $this->assertSame('1', $parsed['records'][0]['employee_code']);
        $this->assertSame('2026-05-28 09:00:00', $parsed['records'][0]['punch_time']);
        $this->assertSame(10, $parsed['records'][0]['verify_type']);
    }

    public function test_it_parses_query_fallback_attlog(): void
    {
        $service = new AdmsParserService();
        $parsed = $service->parseAttLogFromQuery([
            'PIN' => '1',
            'DateTime' => '2026-05-28 09:00:00',
            'Status' => '1',
            'Verify' => '2',
        ]);

        $this->assertCount(1, $parsed['records']);
        $this->assertSame('1', $parsed['records'][0]['employee_code']);
        $this->assertSame(1, $parsed['records'][0]['punch_state']);
        $this->assertSame(2, $parsed['records'][0]['verify_type']);
    }
}
