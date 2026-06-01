<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeMasterSeeder extends Seeder
{
    public function run(): void
    {
        // employee_code matches the biometric device PIN exactly
        $employees = [
            ['code' => '1',  'name' => 'Emad',           'dept' => 'IT',           'desig' => 'Software Engineer',      'shift' => '09:00'],
            ['code' => '2',  'name' => 'Nikita',          'dept' => 'Sales',        'desig' => 'Sales Executive',        'shift' => '09:00'],
            ['code' => '3',  'name' => 'Nick',            'dept' => 'Production',   'desig' => 'Production Officer',     'shift' => '09:00'],
            ['code' => '4',  'name' => 'Rustom',          'dept' => 'Production',   'desig' => 'Production Manager',     'shift' => '09:00'],
            ['code' => '5',  'name' => 'Mazhar',          'dept' => 'IT',           'desig' => 'IT Manager',             'shift' => '09:00'],
            ['code' => '6',  'name' => 'Mehul',           'dept' => 'IT',           'desig' => 'Network Engineer',       'shift' => '09:00'],
            ['code' => '7',  'name' => 'Carol',           'dept' => 'Admin',        'desig' => 'Admin Executive',        'shift' => '09:00'],
            ['code' => '8',  'name' => 'Hasan Mirza',     'dept' => 'Admin',        'desig' => 'Administrative Officer', 'shift' => '09:00'],
            ['code' => '9',  'name' => 'Esha',            'dept' => 'Accounts',     'desig' => 'Accounts Executive',     'shift' => '09:00'],
            ['code' => '10', 'name' => 'Ankita',          'dept' => 'Accounts',     'desig' => 'Accounts Assistant',     'shift' => '09:00'],
            ['code' => '11', 'name' => 'Shivani',         'dept' => 'HR',           'desig' => 'HR Executive',           'shift' => '09:00'],
            ['code' => '12', 'name' => 'Sakshi',          'dept' => 'HR',           'desig' => 'HR Assistant',           'shift' => '09:00'],
            ['code' => '13', 'name' => 'Walid',           'dept' => 'IT',           'desig' => 'System Administrator',   'shift' => '09:00'],
            ['code' => '14', 'name' => 'Gurmeet',         'dept' => 'Admin',        'desig' => 'Admin Officer',          'shift' => '09:00'],
            ['code' => '15', 'name' => 'Abdulbasit',      'dept' => 'Accounts',     'desig' => 'Senior Accountant',      'shift' => '09:00'],
            ['code' => '16', 'name' => 'Mayuresh',        'dept' => 'Admin',        'desig' => 'Admin Coordinator',      'shift' => '09:00'],
            ['code' => '17', 'name' => 'Yogesh',          'dept' => 'IT',           'desig' => 'Software Developer',     'shift' => '09:00'],
            ['code' => '18', 'name' => 'Pratish',         'dept' => 'IT',           'desig' => 'Frontend Developer',     'shift' => '09:00'],
            ['code' => '19', 'name' => 'Shivendra',       'dept' => 'Sales',        'desig' => 'Sales Manager',          'shift' => '09:00'],
            ['code' => '20', 'name' => 'Saad',            'dept' => 'Sales',        'desig' => 'Business Developer',     'shift' => '09:00'],
            ['code' => '21', 'name' => 'Digambar',        'dept' => 'Production',   'desig' => 'Production Supervisor',  'shift' => '09:00'],
            ['code' => '22', 'name' => 'Shradha',         'dept' => 'HR',           'desig' => 'HR Manager',             'shift' => '09:00'],
            ['code' => '23', 'name' => 'Abhishek Bhoir',  'dept' => 'Production',   'desig' => 'Production Executive',   'shift' => '09:00'],
            ['code' => '24', 'name' => 'Altamash',        'dept' => 'Admin',        'desig' => 'Admin Manager',          'shift' => '09:00'],
            ['code' => '25', 'name' => 'Reeba',           'dept' => 'HR',           'desig' => 'HR Coordinator',         'shift' => '09:00'],
            ['code' => '26', 'name' => 'Sunita',          'dept' => 'Accounts',     'desig' => 'Accounts Officer',       'shift' => '09:00'],
            ['code' => '27', 'name' => 'Suhail Khan',     'dept' => 'Sales',        'desig' => 'Sales Representative',   'shift' => '09:00'],
            ['code' => '28', 'name' => 'Gayatri',         'dept' => 'Sales',        'desig' => 'Marketing Executive',    'shift' => '09:00'],
            ['code' => '29', 'name' => 'Sunil',           'dept' => 'Admin',        'desig' => 'Office Assistant',       'shift' => '09:00'],
            ['code' => '30', 'name' => 'Surekha',         'dept' => 'Production',   'desig' => 'QA Executive',           'shift' => '09:00'],
            ['code' => '31', 'name' => 'Kajal',           'dept' => 'HR',           'desig' => 'HR Executive',           'shift' => '09:00'],
            ['code' => '32', 'name' => 'Zaheer',          'dept' => 'Production',   'desig' => 'Production Assistant',   'shift' => '09:00'],
            ['code' => '33', 'name' => 'Kashif',          'dept' => 'IT',           'desig' => 'IT Support Engineer',    'shift' => '09:00'],
            ['code' => '34', 'name' => 'Chinmay',         'dept' => 'Admin',        'desig' => 'Admin Executive',        'shift' => '09:00'],
            ['code' => '35', 'name' => 'Atif',            'dept' => 'Accounts',     'desig' => 'Accounts Assistant',     'shift' => '09:00'],
            ['code' => '36', 'name' => 'Sudhanshu',       'dept' => 'IT',           'desig' => 'Backend Developer',      'shift' => '09:00'],
        ];

        foreach ($employees as $emp) {
            Employee::updateOrCreate(
                ['employee_code' => $emp['code']],
                [
                    'name'                   => $emp['name'],
                    'department'             => $emp['dept'],
                    'designation'            => $emp['desig'],
                    'shift_start'            => $emp['shift'] . ':00',
                    'shift_end'              => '18:00:00',
                    'late_threshold_minutes' => 30,
                    'is_active'              => true,
                ]
            );
        }

        $this->command->info('EmployeeMasterSeeder: 36 employees seeded.');
    }
}
