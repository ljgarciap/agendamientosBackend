<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class EmployeeUserSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            ['name' => 'Juan Perez', 'email' => 'juan@example.com'],
            ['name' => 'Maria Gomez', 'email' => 'maria@example.com'],
            ['name' => 'Carlos Lopez', 'email' => 'carlos@example.com'],
        ];

        foreach ($employees as $data) {
            if (!User::where('email', $data['email'])->exists()) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => bcrypt('password'),
                ]);
                $user->assignRole('user'); // 'user' is the role for employees/providers
                $this->command->info("Employee {$data['name']} created.");
            }
        }
    }
}
