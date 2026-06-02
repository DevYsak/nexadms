<?php

use App\Http\Controllers\IclockController;
use Illuminate\Support\Facades\Route;

/*
 * ZKTeco ADMS Push Protocol routes.
 * No web middleware — no sessions, no CSRF, no cookies.
 * Device connects directly over HTTP/HTTPS.
 */

Route::prefix('iclock')->group(function () {
    // Step 1 & 2: Handshake (GET) + Push (POST)
    Route::match(['GET', 'POST'], '/cdata', [IclockController::class, 'cdata']);

    // Step 4: Device polls for pending commands
    Route::get('/getrequest', [IclockController::class, 'getrequest']);

    // Step 5: Device replies to commands
    Route::post('/devicecmd', [IclockController::class, 'devicecmd']);
});
