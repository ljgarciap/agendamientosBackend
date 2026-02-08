<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\User;
use App\Models\Company;

echo "--- COMPAÑIAS ---\n";
foreach (Company::all() as $c) {
    echo "ID: {$c->id} | Name: {$c->name}\n";
}

echo "\n--- USUARIOS ---\n";
foreach (User::all() as $u) {
    echo "ID: {$u->id} | Email: {$u->email} | Company ID: " . ($u->company_id ?? 'NULL (Global)') . " | Name: {$u->name}\n";
}
