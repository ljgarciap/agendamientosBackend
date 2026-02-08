<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{

    public function index(Request $request)
    {
        // Public or Auth generic? If public, maybe pass company_id as query param?
        // But for logged in user, use their Company ID.
        // For now, let's assume it's for logged in context or passed via query for public.
        
        $companyId = $request->query('company_id');
        if (Auth::check()) {
            $companyId = Auth::user()->company_id;
        }

        if (!$companyId) {
             return response()->json(['data' => []]); // Or all? Better safe than sorry.
        }

        return response()->json(['data' => Service::where('company_id', $companyId)->get()]);
    }

    public function store(Request $request)
    {
        // ... (auth check)
        if (!Auth::user()->can('manage services')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'detail' => 'nullable|string',
            'icon' => 'nullable|string',
            'category' => 'required|string',
            'price' => 'nullable|numeric',
            'duration_minutes' => 'nullable|integer',
            'location_type' => 'required|in:onsite,delivery,both',
            'image' => 'nullable|image|max:2048', // Added image validation
        ]);

        $data = $validated;
        $data['company_id'] = Auth::user()->company_id;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('service-images', 'public');
            $data['image_url'] = route('service.image', ['filename' => basename($path)]);
        }

        return response()->json(['data' => Service::create($data)], 201);
    }

    public function show($id)
    {
        // Public or Private? If public, show. If private, check company.
        // Let's assume public read is okay for now, OR restrict to company.
        // Given constraints: "Admin creates services", implies private management.
        
        $service = Service::findOrFail($id);
        
        // If strict mode (only see own company services in admin panel)
        // But clients need to see them too.
        // Let's leave show open or check context. 
        // For simpler implementation now: allow public read (clients need it), 
        // but store/update/destroy must be secured.
        
        return response()->json(['data' => $service]);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->can('manage services')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service = Service::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'detail' => 'nullable|string',
            'icon' => 'nullable|string',
            'category' => 'sometimes|string',
            'price' => 'nullable|numeric', 
            'duration_minutes' => 'nullable|integer',
            'location_type' => 'sometimes|in:onsite,delivery,both',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('service-images', 'public');
            $validated['image_url'] = route('service.image', ['filename' => basename($path)]);
        }

        $service->update($validated);

        return response()->json(['data' => $service]);
    }

    public function destroy($id)
    {
        // ... (existing destroy logic)
        $user = Auth::user();
        if (!$user->can('manage services')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service = Service::where('id', $id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $service->delete();

        return response()->noContent();
    }

    public function getServiceImage($filename)
    {
        $path = storage_path('app/public/service-images/' . $filename);
        if (!file_exists($path)) {
            abort(404);
        }
        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        ]);
    }
}
