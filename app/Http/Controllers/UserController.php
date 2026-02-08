<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = $request->user();

        // Security: Scope to current user's company
        $query = User::query();
        
        if ($currentUser && $currentUser->company_id) {
            $query->where('company_id', $currentUser->company_id);
        } else {
            // Strict: If user has no company (and not super admin), return empty
            if (!$currentUser || !$currentUser->hasRole('super_admin')) {
                return response()->json(['data' => []]);
            }
        }

        // Allow filtering by role (e.g., 'user', 'client', 'admin')
        if ($request->has('role')) {
            $query->role($request->role);
        }
        
        return response()->json(['data' => $query->get()]);
    }
    public function getProfilePhoto($filename)
    {
        $path = storage_path('app/public/profile-photos/' . $filename);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'password' => 'sometimes|string|min:8',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->load('roles')
        ]);
    }
}
