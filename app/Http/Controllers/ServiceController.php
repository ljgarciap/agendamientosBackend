<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Service;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{

    public function index()
    {
        return response()->json(['data' => Service::all()]);
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
        ]);

        return response()->json(['data' => Service::create($validated)], 201);
    }

    public function show(Service $service)
    {
        return response()->json(['data' => $service]);
    }

    public function update(Request $request, Service $service)
    {
        if (!Auth::user()->can('manage services')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'detail' => 'nullable|string',
            'icon' => 'nullable|string',
            'category' => 'string',
        ]);

        $service->update($validated);

        return response()->json(['data' => $service]);
    }

    public function destroy(Service $service)
    {
        if (!Auth::user()->can('manage services')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $service->delete();

        return response()->noContent();
    }
}
