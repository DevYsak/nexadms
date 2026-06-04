<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeSyncController extends Controller
{
    /**
     * Receive employees from HRMS and upsert by employee_code.
     *
     * Expects JSON body:
     *   { "employees": [ { "employee_code": "001", "name": "...", ... }, ... ] }
     *
     * Returns summary of created/updated counts.
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employees'                          => ['required', 'array', 'min:1'],
            'employees.*.employee_code'          => ['required', 'string', 'max:20'],
            'employees.*.name'                   => ['required', 'string', 'max:100'],
            'employees.*.department'             => ['sometimes', 'string', 'max:100'],
            'employees.*.designation'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'employees.*.email'                  => ['sometimes', 'nullable', 'email', 'max:150'],
            'employees.*.phone'                  => ['sometimes', 'nullable', 'string', 'max:20'],
            'employees.*.is_active'              => ['sometimes', 'boolean'],
            'employees.*.shift_start'            => ['sometimes', 'nullable', 'date_format:H:i,H:i:s'],
            'employees.*.shift_end'              => ['sometimes', 'nullable', 'date_format:H:i,H:i:s'],
            'employees.*.late_threshold_minutes' => ['sometimes', 'integer', 'min:0', 'max:1440'],
        ]);

        $created = 0;
        $updated = 0;

        foreach ($validated['employees'] as $data) {
            $code = $data['employee_code'];

            $exists = Employee::where('employee_code', $code)->exists();

            Employee::updateOrCreate(
                ['employee_code' => $code],
                collect($data)->except('employee_code')->toArray()
            );

            $exists ? $updated++ : $created++;
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'total'   => $created + $updated,
        ]);
    }
}
