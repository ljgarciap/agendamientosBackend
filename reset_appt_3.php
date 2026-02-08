<?php

use App\Models\Appointment;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apptId = 3;
$appt = Appointment::find($apptId);

if (!$appt) {
    echo "Appointment $apptId not found.\n";
    exit;
}

// Set to tomorrow same time to be safe
$future = Carbon::now()->addDay()->setHour(10)->setMinute(0);

$appt->status = 'confirmed';
$appt->scheduled_at = $future;
$appt->save();

echo "Reset Appointment $apptId:\n";
echo "Status: {$appt->status}\n";
echo "Scheduled At: {$appt->scheduled_at}\n";
