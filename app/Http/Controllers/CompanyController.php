<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    // Public: List all companies for selection screen
    public function index()
    {
        return Company::select('id', 'name', 'logo_url', 'primary_color_hex', 'secondary_color_hex', 'background_color_hex', 'about_us')->get();
    }

    // Public: Get specific company branding
    public function show($id)
    {
        return Company::findOrFail($id);
    }

    // Protected: Create new company (Super Admin only)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048', // File validation
            'logo_url' => 'nullable|url',        // URL validation (fallback)
            'primary_color_hex' => 'nullable|string',
            'secondary_color_hex' => 'nullable|string',
            'background_color_hex' => 'nullable|string',
            'about_us' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('company-logos', 'public');
            $validated['logo_url'] = asset('storage/' . $path);
        }
        // If no file but logo_url is present, it's already in $validated

        $company = Company::create($validated);
        return response()->json($company, 201);
    }

    // Public: Serve company logo with CORS headers
    public function getLogo($filename)
    {
        $path = storage_path('app/public/company-logos/' . $filename);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        ]);
    }

    // Protected: Update company logic
    public function update(Request $request, $id)
    {
        \Illuminate\Support\Facades\Log::info('Updating company ' . $id);
        
        $user = $request->user();
        // Allow if user belongs to this company OR is super admin
        if ($user->company_id != $id && !$user->hasRole('super_admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $company = Company::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'logo' => 'nullable|image|max:2048',
            'logo_url' => 'nullable|url',
            'primary_color_hex' => 'nullable|string',
            'secondary_color_hex' => 'nullable|string',
            'background_color_hex' => 'nullable|string',
            'about_us' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);
        
        if ($request->hasFile('logo')) {
            try {
                $path = $request->file('logo')->store('company-logos', 'public');
                // Use a dedicated route to serve the image with CORS headers
                $validated['logo_url'] = route('company.logo', ['filename' => basename($path)]);
                \Illuminate\Support\Facades\Log::info('Logo updated to: ' . $validated['logo_url']);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Error storing file: ' . $e->getMessage());
            }
        }
        
        $company->update($validated);
        return response()->json($company);
    }

    // Protected: Soft delete company
    public function destroy($id)
    {
        $company = Company::findOrFail($id);
        $company->delete(); // Soft delete provided trait is used
        return response()->json(['message' => 'Company deleted successfully']);
    }

    // Protected: Get Admins of a company
    public function indexAdmins($id)
    {
        // Verify company exists
        Company::findOrFail($id);
        
        // Return users of this company that have role 'admin'
        // Using whereHas to check role
        $admins = \App\Models\User::where('company_id', $id)
                    ->role('admin')
                    ->get();
                    
        return response()->json($admins);
    }

    // Protected: Create new admin for a company
    public function storeAdmin(Request $request, $id)
    {
        // 1. Validate Company Exists
        $company = Company::findOrFail($id);

        // 2. Validate User Input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255', // Removed unique:users because of multi-tenant
            'password' => 'required|string|min:8',
        ]);

        // 3. Create User Scoped to Company
        // Check uniqueness manually for this company
        $exists = \App\Models\User::where('email', $validated['email'])
                                ->where('company_id', $id)
                                ->exists();
        if ($exists) {
            return response()->json(['message' => 'The email has already been taken for this company.'], 422);
        }

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'company_id' => $id,
        ]);

        // 4. Assign Admin Role
        $user->assignRole('admin');

        return response()->json($user, 201);
    }
    
    // Protected: Update Admin
    public function updateAdmin(Request $request, $companyId, $userId) 
    {
         $user = \App\Models\User::where('company_id', $companyId)->where('id', $userId)->firstOrFail();
         
         $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255',
            'password' => 'sometimes|string|min:8',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }
        
        // If email changes, check uniqueness again
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
             $exists = \App\Models\User::where('email', $validated['email'])
                                ->where('company_id', $companyId)
                                ->where('id', '!=', $userId)
                                ->exists();
            if ($exists) {
                return response()->json(['message' => 'The email has already been taken for this company.'], 422);
            }
        }
        
        $user->update($validated);
        return response()->json($user);
    }

    // Protected: Soft Delete Admin
    public function destroyAdmin($companyId, $userId)
    {
        $company = Company::findOrFail($companyId);
        $user = \App\Models\User::where('company_id', $companyId)->where('id', $userId)->firstOrFail();
        $user->delete();
        return response()->json(['message' => 'Admin deleted successfully']);
    }

    // Protected: Proxy Geocoding Request to Nominatim
    public function geocode(Request $request)
    {
        $query = $request->input('q');
        if (!$query) {
            return response()->json(['error' => 'Query is required'], 400);
        }

        // Use Laravel HTTP Client
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'User-Agent' => 'AgendamientosApp/1.0 (contact@softclass.co)' // Respect OSM Policy
            ])->get('https://nominatim.openstreetmap.org/search', [
                'q' => $query,
                'format' => 'json',
                'limit' => 1
            ]);
            
            return $response->json();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error contacting geocoding service'], 500);
        }
    }
}
