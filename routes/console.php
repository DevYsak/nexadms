<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('biometric:tail {channel=biometric} {--lines=80}', function () {
    $channel = (string) $this->argument('channel');
    $lines = (int) $this->option('lines');
    $path = storage_path("logs/{$channel}.log");

    if (! file_exists($path)) {
        $this->error("Log file not found: {$path}");
        return self::FAILURE;
    }

    $content = file($path, FILE_IGNORE_NEW_LINES) ?: [];
    foreach (array_slice($content, -1 * max($lines, 1)) as $line) {
        $this->line($line);
    }

    return self::SUCCESS;
})->purpose('Print the tail of the biometric or Laravel log file');
