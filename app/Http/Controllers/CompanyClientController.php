<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Appointment;

class CompanyClientController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'User not associated with a company'], 403);
        }

        // Get users who have appointments with this company OR are registered with this company
        // User clarified: "clients associated with the company are clients even if they haven't taken a service"
        try {
            $clients = User::role('client')
                ->where('company_id', $companyId) // Filter by company ownership
            ->withCount(['appointments' => function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            }])
            // Add last appointment date
            // We can't easily add a single column via withCount/with without subquery or relation
            // Let's load the latest appointment
            ->with(['appointments' => function($query) use ($companyId) {
                $query->where('company_id', $companyId)->latest()->limit(1);
            }])
            ->get()
            ->map(function($client) {
                $lastAppt = $client->appointments->first();
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'birth_date' => $client->birth_date,
                    'profile_photo_url' => $client->profile_photo_url,
                    'appointments_count' => $client->appointments_count,
                'last_appointment' => $lastAppt ? $lastAppt->scheduled_at : null, // Use scheduled_at not date
                ];
            });
            
        } catch (\Exception $e) {
            // Fallback if role missing or other error
            return response()->json([], 200);
        }

        return response()->json($clients);
    }
}
