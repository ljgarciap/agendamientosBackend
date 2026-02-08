<?php

use App\Models\Appointment;
use Carbon\Carbon;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apptId = 3; // From previous debug session
$appt = Appointment::find($apptId);

if (!$appt) {
    echo "Appointment $apptId not found.\n";
    exit;
}

echo "Config Timezone: " . config('app.timezone') . "\n";
echo "Server Time (now): " . now()->format('Y-m-d H:i:s') . "\n";

echo "\nAppointment Details:\n";
echo "ID: {$appt->id}\n";
echo "Status: {$appt->status}\n";
echo "Scheduled At (DB): {$appt->scheduled_at}\n";

$scheduledAt = Carbon::parse($appt->scheduled_at);
echo "Scheduled At (Parsed): " . $scheduledAt->format('Y-m-d H:i:s') . "\n";

$now = now();
$diff = $now->diffInMinutes($scheduledAt, false);
echo "Minutes remaining: $diff\n";

if ($scheduledAt->isPast()) {
    echo "Result: PAST (Blocking Cancellation)\n";
} elseif ($now->addHour()->gt($scheduledAt)) {
    echo "Result: LESS THAN 1 HOUR (Blocking Cancellation)\n";
} else {
    echo "Result: ALLOWED\n";
}
