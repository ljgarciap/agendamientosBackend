<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AppointmentController;
use App\Models\User;

function testUserFlow($email, $expectedRole) {
    echo "\n--- Testing Flow for: $email ---\n";
    
    // 1. Simulate Login/Auth
    $user = User::where('email', $email)->first();
    if (!$user) {
        echo "User not found!\n"; 
        return;
    }
    
    // Manually act as user
    Auth::login($user);
    echo "Logged in. ID: " . $user->id . "\n";
    echo "Has Role '$expectedRole'? " . ($user->hasRole($expectedRole) ? 'YES' : 'NO') . "\n";
    
    // 2. Call Controller
    $controller = new AppointmentController();
    $response = $controller->index();
    
    // 3. Inspect Response
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    if (isset($data['data'])) {
        echo "Success! Appointments found: " . count($data['data']) . "\n";
        if (count($data['data']) > 0) {
            echo "First Appt ID: " . $data['data'][0]['id'] . "\n";
        }
    } else {
        echo "Failed or Empty. Response: " . substr($content, 0, 100) . "\n";
    }
}

testUserFlow('client@example.com', 'client');
testUserFlow('juan@example.com', 'user');
testUserFlow('admin@example.com', 'admin');
