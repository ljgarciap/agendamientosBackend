<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{

    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $data = Appointment::with(['user', 'service', 'assignedUser'])->get();
            return response()->json(['data' => $data]);
        }

        if ($user->hasRole('client')) {
            $data = Appointment::with(['service', 'assignedUser'])->where('user_id', $user->id)->get();
            return response()->json(['data' => $data]);
        }

        if ($user->hasRole('user')) {
             $data = Appointment::with(['user', 'service'])->where('assigned_to', $user->id)->get();
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
            'service_id' => $validated['service_id'],
            'scheduled_at' => $validated['scheduled_at'],
            'location' => $validated['location'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => 'pending',
        ]);

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

            $validated = $request->validate([
                'status' => 'string|in:pending,confirmed,completed,cancelled',
                'assigned_to' => 'nullable|exists:users,id',
            ]);
            $appointment->update($validated);
            return response()->json(['data' => $appointment]);
        }

        // 2. Employee (User role) can only mark as completed if assigned to them
        if ($user->hasRole('user') && $appointment->assigned_to == $user->id) {
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
