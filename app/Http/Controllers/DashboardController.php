<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'User not associated with a company'], 403);
        }

        // 1. Registered Clients (Users with role 'client' belonging to this company)
        // User requested to see ALL clients associated with the company, even if no appointments yet.
        try {
             $activeClientsCount = User::role('client')
                ->where('company_id', $companyId)
                ->count();
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
             $activeClientsCount = 0; 
        }

        // 2. Total Appointments
        $totalAppointments = Appointment::where('company_id', $companyId)->count();

        // 3. Revenue
        // Calculate in PHP to avoid SQL driver binding issues with strings
        $revenue = Appointment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->with('service:id,price') // Eager load only price
            ->get()
            ->sum(function ($appointment) {
                return $appointment->service ? $appointment->service->price : 0;
            });

        // 4. Top Services
        $topServices = Service::where('company_id', $companyId)
            ->withCount(['appointments' => function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            }])
            ->orderBy('appointments_count', 'desc')
            ->take(5)
            ->get();

        // 5. Top Employees (Most assigned appointments)
        // Employees have role 'user' (providers) in this system based on Seeder.
        // Role 'employee' does NOT exist.
        try {
            $topEmployees = User::where('company_id', $companyId)
                ->role('user') 
                ->withCount(['assignedAppointments' => function($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }])
                ->orderBy('assigned_appointments_count', 'desc')
                ->take(5)
                ->get();
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            $topEmployees = collect([]);
        }

        return response()->json([
            'active_clients' => $activeClientsCount,
            'total_appointments' => $totalAppointments,
            'revenue' => $revenue,
            'top_services' => $topServices,
            'top_employees' => $topEmployees,
        ]);
    }
}
