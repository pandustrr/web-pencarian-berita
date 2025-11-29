<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SystemController extends Controller
{
    /**
     * Import data sederhana
     */
    public function importCSV()
    {
        return response()->json([
            'success' => true,
            'message' => 'Python handle CSV processing'
        ]);
    }

    /**
     * System info sederhana
     */
    public function systemInfo()
    {
        return response()->json([
            'status' => 'running',
            'python_connected' => false, // Diisi oleh Python check
            'timestamp' => now()->toISOString()
        ]);
    }
}
