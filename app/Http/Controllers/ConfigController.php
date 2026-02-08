<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigController extends Controller
{
    public function index()
    {
        // Return key-value pair for easier frontend consumption
        $configs = DB::table('configurations')->pluck('value', 'key');
        return response()->json($configs);
    }
}
