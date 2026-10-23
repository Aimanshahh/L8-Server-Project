<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validate the incoming data from Flutter
        $request->validate([
            'token' => 'required|string',
            'user_id' => 'required|integer',
            'platform' => 'nullable|string'
        ]);

        // 2. Save or update the token
        DB::table('device_tokens')->updateOrInsert(
            ['user_id' => $request->user_id],
            [
                'token' => $request->token,
                'platform' => $request->platform ?? 'android',
                'updated_at' => now()
            ]
        );

        // 3. Return response
        return response()->json([
            'status' => 'success',
            'message' => 'Device token saved successfully.'
        ], 200);
    }
}