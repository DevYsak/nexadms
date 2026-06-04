<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = env('BIOMETRIC_API_KEY');

        if ($configured && $request->header('X-Api-Key') !== $configured) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
