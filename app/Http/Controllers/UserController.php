<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Allow filtering by role (e.g., 'user', 'client', 'admin')
        if ($request->has('role')) {
            $users = User::role($request->role)->get();
            // average_rating is automatically appended by the User model
            return response()->json(['data' => $users]);
        }
        
        return response()->json(['data' => User::all()]);
    }
}
