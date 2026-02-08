<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees (users with role 'user') for the current company.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Ensure only company admins can access this (or super admins masquerading)
        // Assuming middleware handles basic auth, but let's be safe
        if (!$user->company_id) {
             return response()->json(['message' => 'User does not belong to a company'], 400);
        }

        $employees = User::where('company_id', $user->company_id)
            ->role('user') // Filter by role 'user'
            ->with('services') // Eager load services
            ->get();

        return response()->json(['data' => $employees]);
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'profile_photo_url' => 'nullable|string', // URL or Path
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id', // Basic check
        ]);

        DB::beginTransaction();
        try {
        // Specific logic for profile photo in store method
        $profilePhotoUrl = $validated['profile_photo_url'] ?? null;
        // Check if file is present separately (the validation handles string/url, but if file...)
        // Actually, validation 'profile_photo_url' => 'nullable|string' implies URL.
        // But if postMultipart sends a file, we need to handle it.
        // The store method validation doesn't include 'profile_photo' file validation explicitly, 
        // but let's check if request has file.
        
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('profile-photos', 'public');
            $profilePhotoUrl = route('user.profile_photo', ['filename' => basename($path)]);
        }

            $employee = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'company_id' => $user->company_id,
                'profile_photo_url' => $profilePhotoUrl,
            ]);

            $employee->assignRole('user');

            if (isset($validated['service_ids'])) {
                // Verify services belong to the same company
                $count = Service::where('company_id', $user->company_id)
                    ->whereIn('id', $validated['service_ids'])
                    ->count();
                
                if ($count !== count($validated['service_ids'])) {
                     throw new \Exception("One or more services do not belong to this company.");
                }

                $employee->services()->sync($validated['service_ids']);
            }

            DB::commit();
            return response()->json(['data' => $employee->load('services')], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = Auth::user();
        $employee = User::where('company_id', $user->company_id)
            ->where('id', $id)
            ->with('services')
            ->firstOrFail();

        return response()->json(['data' => $employee]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $employee = User::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $employee->id,
            'password' => 'nullable|string|min:8',
            'profile_photo_url' => 'nullable|string',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name'])) $employee->name = $validated['name'];
            if (isset($validated['email'])) $employee->email = $validated['email'];
            if (isset($validated['password'])) $employee->password = Hash::make($validated['password']);
            
            // Handle Profile Photo Upload
            if ($request->hasFile('profile_photo')) {
                $path = $request->file('profile_photo')->store('profile-photos', 'public');
                $employee->profile_photo_url = route('user.profile_photo', ['filename' => basename($path)]);
            } elseif (isset($validated['profile_photo_url'])) {
                $employee->profile_photo_url = $validated['profile_photo_url'];
            }
            
            $employee->save();

            if (isset($validated['service_ids'])) {
                 // Verify services belong to the same company
                $count = Service::where('company_id', $user->company_id)
                    ->whereIn('id', $validated['service_ids'])
                    ->count();
                
                if ($count !== count($validated['service_ids'])) {
                     throw new \Exception("One or more services do not belong to this company.");
                }
                
                $employee->services()->sync($validated['service_ids']);
            }

            DB::commit();
            return response()->json(['data' => $employee->load('services')]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating employee: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $employee = User::where('company_id', $user->company_id)
            ->where('id', $id)
            ->firstOrFail();

        // Prevent deleting self? Although controller is for 'user' role, if admin is 'admin' role, it's fine.
        if ($employee->id === $user->id) {
             return response()->json(['message' => 'Cannot delete yourself'], 400);
        }

        $employee->delete(); // Soft delete

        return response()->json(['message' => 'Employee deleted successfully']);
    }
}
