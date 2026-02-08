<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Restoring Users ---\n";

$email = 'test@test.co';
$password = 'password'; // Default password for restored users

// 1. Restore for Company 1 (Barbería Estilo)
$user1 = User::firstOrCreate(
    ['email' => $email, 'company_id' => 1],
    ['name' => 'Test User Barberia', 'password' => Hash::make($password)]
);
$user1->assignRole('user');
echo "Restored User for Company 1: ID {$user1->id}\n";

// 2. Restore for Company 4 (Softclass)
$user4 = User::firstOrCreate(
    ['email' => $email, 'company_id' => 4],
    ['name' => 'Test User Softclass', 'password' => Hash::make($password)]
);
$user4->assignRole('user');
echo "Restored User for Company 4: ID {$user4->id}\n";

echo "Done. Password set to: '$password'\n";
