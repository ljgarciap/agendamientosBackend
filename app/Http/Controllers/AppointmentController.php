<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use App\Notifications\AppointmentCreatedNotification;
use App\Notifications\AppointmentAssignedNotification;
use App\Notifications\AppointmentStatusChangedNotification;
use Illuminate\Support\Facades\Notification;

class AppointmentController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $data = Appointment::with(['user', 'service', 'assignedUser'])
                        ->where('company_id', $user->company_id)
                        ->get();
            return response()->json(['data' => $data]);
        }

        if ($user->hasRole('client')) {
            $data = Appointment::with(['service', 'assignedUser'])
                        ->where('user_id', $user->id)
                        ->where('company_id', $user->company_id)
                        ->get();
            return response()->json(['data' => $data]);
        }

        if ($user->hasRole('user')) {
             $data = Appointment::with(['user', 'service'])
                        ->where('assigned_to', $user->id)
                        ->where('company_id', $user->company_id)
                        ->get();
             return response()->json(['data' => $data]);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->hasRole('client')) {
             return response()->json(['message' => 'Only clients can request appointments'], 403);
        }

        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date',
            'location' => 'required|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $appointment = Appointment::create([
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
            'service_id' => $validated['service_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'location' => $validated['location'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => 'pending',
        ]);

        // Notify Company Admins
        $admins = \App\Models\User::role('admin')
                    ->where('company_id', $appointment->company_id)
                    ->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new AppointmentCreatedNotification($appointment->load(['user', 'service'])));
        }

        return response()->json(['data' => $appointment], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        // Admin can assign and change status.
        // User (employee) could potentially change status if assigned to them (feature enhancement)
        
        $user = Auth::user();

        // 1. Admin can do anything
        // 1. Admin can do anything, BUT let's prevent accidental re-assignments of completed tasks unless explicitly needed?
        // User asked "Admin shouldn't be able to restart a closed service".
        if ($user->hasRole('admin')) {
             if (in_array($appointment->status, ['completed', 'cancelled'])) {
                 // For now, return error if trying to modify a closed appointment
                 return response()->json(['message' => 'Cannot modify a closed appointment'], 400); 
             }

            $oldStatus = $appointment->status;
            $oldAssignedTo = $appointment->assigned_to;

            $validated = $request->validate([
                'status' => 'string|in:pending,confirmed,completed,cancelled',
                'assigned_to' => 'nullable|exists:users,id',
            ]);
            $appointment->update($validated);

            // Notify Client if status changed
            if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
                $appointment->user->notify(new AppointmentStatusChangedNotification($appointment->load(['service'])));
            }

            // Notify Employee if assigned or re-assigned
            if (isset($validated['assigned_to']) && $validated['assigned_to'] != $oldAssignedTo) {
                $employee = \App\Models\User::find($validated['assigned_to']);
                if ($employee) {
                    $employee->notify(new AppointmentAssignedNotification($appointment->load(['user', 'service'])));
                }
            }

            return response()->json(['data' => $appointment]);
        }

        // 2. Employee (User role) can only mark as completed if assigned to them
        if ($user->hasRole('user') && $appointment->assigned_to == $user->id) {
             $oldStatus = $appointment->status;
             $validated = $request->validate([
                'status' => 'string|in:in_progress,completed',
                'employee_latitude' => 'nullable|required_if:status,in_progress',
                'employee_longitude' => 'nullable|required_if:status,in_progress',
            ]);
            
            $appointment->update([
                'status' => $validated['status'],
                'employee_latitude' => $validated['employee_latitude'] ?? $appointment->employee_latitude,
                'employee_longitude' => $validated['employee_longitude'] ?? $appointment->employee_longitude,
            ]);

             // Notify Client if status changed
            if ($validated['status'] !== $oldStatus) {
                $appointment->user->notify(new AppointmentStatusChangedNotification($appointment->load(['service'])));
            }

            return response()->json(['data' => $appointment]);
        }

        // 3. Client (or any user) can cancel their own appointment if > 1 hour before
        if ($appointment->user_id == $user->id) {
            if (!in_array($appointment->status, ['pending', 'confirmed'])) {
                return response()->json(['message' => 'Cannot cancel an appointment that is ' . $appointment->status], 400);
            }

             $validated = $request->validate([
                'status' => 'required|in:cancelled',
            ]);

            $scheduledAt = \Carbon\Carbon::parse($appointment->scheduled_at);
            $now = now();
            // Debug/Safety: If scheduledAt in past, clearly block
            if ($scheduledAt->isPast()) {
                 return response()->json(['message' => 'Cannot cancel: Appointment is in the past ' . $scheduledAt->diffForHumans()], 400);
            }

            if ($now->addHour()->gt($scheduledAt)) {
                $diff = $now->diffInMinutes($scheduledAt);
                return response()->json(['message' => "Cannot cancel within 1 hour of appointment. Time remaining: {$diff} minutes"], 400); 
            }
            
            $appointment->update(['status' => 'cancelled']);

            // Notify Admins about client cancellation
            $admins = \App\Models\User::role('admin')
                        ->where('company_id', $appointment->company_id)
                        ->get();
            if ($admins->isNotEmpty()) {
                // Reuse AppointmentStatusChangedNotification or create a generic one? 
                // Let's use AppointmentStatusChangedNotification for now.
                Notification::send($admins, new AppointmentStatusChangedNotification($appointment->load(['user', 'service'])));
            }

            return response()->json(['data' => $appointment]);
        }

        return response()->json(['message' => 'Unauthorized'], 403);
    }

    public function rate(Request $request, Appointment $appointment)
    {
        if (Auth::id() !== $appointment->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($appointment->status !== 'completed') {
            return response()->json(['message' => 'Appointment must be completed to rate'], 400);
        }

        if ($appointment->rating != null) {
            return response()->json(['message' => 'Appointment already rated'], 400);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_comment' => 'nullable|string'
        ]);

        $appointment->update($validated);

        // Update Employee Average Rating
        if ($appointment->assigned_to) {
            $employee = \App\Models\User::find($appointment->assigned_to);
            if ($employee) {
                // Calculate new average
                $average = Appointment::where('assigned_to', $employee->id)
                            ->whereNotNull('rating')
                            ->avg('rating');
                
                $employee->update(['average_rating' => $average]);
            }
        }

        return response()->json(['data' => $appointment]);
    }


    public function show(Appointment $appointment)
    {
        return response()->json(['data' => $appointment]);
    }

    public function destroy(Appointment $appointment)
    {
        // Implement if needed, e.g. for Admin
    }
}
