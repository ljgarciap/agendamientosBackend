<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class ClientUserSeeder extends Seeder
{
    public function run(): void
    {
        // Check if user exists to avoid duplicates
        if (!User::where('email', 'client@example.com')->exists()) {
            $user = User::create([
                'name' => 'Cliente Prueba',
                'email' => 'client@example.com',
                'password' => bcrypt('password'),
            ]);
            $user->assignRole('client');
            $this->command->info('Client User Created successfully.');
        } else {
            $this->command->info('Client User already exists.');
        }
    }
}
