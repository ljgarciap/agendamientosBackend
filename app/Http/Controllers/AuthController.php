<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validate request, but allow email to exist if password is provided for verification
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
        ]);

        $companyId = $validated['company_id'];
        $email = $validated['email'];

        // 2. Check if user already exists in this company
        $existingUser = User::where('email', $email)
            ->where('company_id', $companyId)
            ->first();

        if ($existingUser) {
            // User exists. Verify password to allow "adding role"
            if (!Hash::check($validated['password'], $existingUser->password)) {
                 // Return error if password doesn't match existing account
                 return response()->json([
                     'message' => 'El usuario ya existe y la contraseña es incorrecta.',
                     'errors' => ['email' => ['El correo ya está registrado en esta empresa.']]
                 ], 422);
            }

            // Password correct. Check if they already have 'client' role
            if ($existingUser->hasRole('client')) {
                 return response()->json([
                     'message' => 'Ya estás registrado como cliente en esta empresa.',
                     'user' => $existingUser
                 ], 200); // Or 409 conflict? 200 is fine if we just log them in
            }

            // Assign 'client' role to existing user (e.g. Employee becoming Client)
            $existingUser->assignRole('client');
            $user = $existingUser;

        } else {
            // 3. User does not exist. Create new user.
            // Check global uniqueness if needed (assuming email+company_id unique constraint)
            // But we already checked specific company above.
            
            // Note: If email exists in ANOTHER company, that's fine (multi-tenant by logic).
            // Validate unique for THIS company if not using unique key in DB
             $existsInCompany = User::where('email', $email)->where('company_id', $companyId)->exists();
             if ($existsInCompany) {
                 // Should be caught by $existingUser check, but safety net
             }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'company_id' => $validated['company_id'],
                'birth_date' => $request->birth_date,
                'phone' => $request->phone,
            ]);
            
            // Assign default role
            $user->assignRole('client');
        }

        // 4. Generate Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'), // Return roles so frontend knows
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $companyId = $request->input('company_id');

        // 1. Attempt generic auth to verify password matches email for *some* user
        // Problem: If multiple users have same email, Auth::attempt might pick the wrong one.
        // We need to manually find the user first.

        $query = User::where('email', $credentials['email']);
        
        if ($companyId) {
            // Scoped Login: User must belong to this company
            $query->where('company_id', $companyId);
        } else {
            // Global/SuperAdmin Login: User must have NO company (null)
            $query->whereNull('company_id');
        }

        $user = $query->first();

        if (!$user) {
            // Check if user exists in OTHER company to provide better feedback
            $existsElsewhere = User::where('email', $credentials['email'])->exists();
            if ($existsElsewhere) {
                 return response()->json(['message' => 'Este correo no está registrado en la empresa seleccionada.'], 403);
            }
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
             return response()->json(['message' => 'Contraseña incorrecta.'], 401);
        }

        // Logic check: If companyId provided, ensure user belongs to it (query already did this).
        // If no companyId (Super Admin flow), ensure user is allowed globally.
        if (!$companyId && !$user->hasRole('super_admin')) {
             return response()->json(['message' => 'Unauthorized for global access'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}
