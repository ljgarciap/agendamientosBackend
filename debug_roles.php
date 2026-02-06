<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::with('roles')->get();
foreach ($users as $user) {
    // Mimic AuthController logic
    $rolePayload = $user->getRoleNames()->first() ?? 'client';
    echo "Email: " . str_pad($user->email, 20) . " | Actual Roles: " . str_pad($user->getRoleNames()->implode(','), 15) . " | Login Payload: " . $rolePayload . "\n";
}
