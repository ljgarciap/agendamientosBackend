<?php

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Diagnosing Company Data ---\n";
$companies = Company::all(['id', 'name']);
foreach ($companies as $c) {
    echo "ID: {$c->id} - Name: {$c->name}\n";
}
echo "------------------------------\n";

echo "--- Diagnosing User Data ---\n";
$users = User::where('email', 'test@test.co')->get(['id', 'name', 'email', 'company_id']);
echo "Found " . $users->count() . " users with email 'test@test.co':\n";
foreach ($users as $u) {
    echo " - ID: {$u->id}, Name: {$u->name}, Company ID: " . ($u->company_id ?? 'NULL') . "\n";
}
echo "------------------------------\n";

// 1. Setup
echo "Creating Companies...\n";
$companyA = Company::firstOrCreate(['name' => 'Company A'], ['primary_color' => '#000000']);
$companyB = Company::firstOrCreate(['name' => 'Company B'], ['primary_color' => '#FFFFFF']);

echo "Creating Users with same email (test@test.co)...\n";
$email = 'test@test.co';
$password = 'password123';

// Cleanup first
User::where('email', $email)->forceDelete();

$userA = User::create([
    'name' => 'User A', 
    'email' => $email, 
    'password' => Hash::make($password), 
    'company_id' => $companyA->id
]);
$userA->assignRole('user');

$userB = User::create([
    'name' => 'User B', 
    'email' => $email, 
    'password' => Hash::make($password), 
    'company_id' => $companyB->id
]);
$userB->assignRole('user');

echo "Users created. IDs: A={$userA->id}, B={$userB->id}\n";

// 2. Test Login for Company A
echo "\n--- Attempting Login for Company A (ID: {$companyA->id}) ---\n";
// Simulate AuthController logic
$queryA = User::where('email', $email)->where('company_id', $companyA->id);
$foundUserA = $queryA->first();

if ($foundUserA && Hash::check($password, $foundUserA->password)) {
    echo "SUCCESS: Logged in as {$foundUserA->name} (ID: {$foundUserA->id}) for Company A.\n";
} else {
    echo "FAILED: Could not login for Company A.\n";
}

// 3. Test Login for Company B
echo "\n--- Attempting Login for Company B (ID: {$companyB->id}) ---\n";
$queryB = User::where('email', $email)->where('company_id', $companyB->id);
$foundUserB = $queryB->first();

if ($foundUserB && Hash::check($password, $foundUserB->password)) {
    echo "SUCCESS: Logged in as {$foundUserB->name} (ID: {$foundUserB->id}) for Company B.\n";
} else {
    echo "FAILED: Could not login for Company B.\n";
}

// 4. Test Login for Company B with WRONG Scope (Company A)
echo "\n--- Attempting Login for Company B using Company A Scope (Should Fail) ---\n";
$queryWrong = User::where('email', $email)->where('company_id', $companyA->id); // Looking for user in A
$foundUserWrong = $queryWrong->first(); // Returns User A

// NOTE: AuthController finds correct user first.
// If I send company_id = A, it finds User A.
// If I expect to login as User B... I won't. I will login as User A.
// This is NOT a failure, it's correct behavior.
// But if I use User B's password (if they were different), it would fail.
// Here passwords are same.

if ($foundUserWrong->id == $userB->id) {
     echo "CRITICAL ERROR: Found User B when searching in Company A!\n";
} else {
     echo "Correctly found User A ({$foundUserWrong->name}) when searching in Company A.\n";
}

echo "\nTest Complete.\n";
