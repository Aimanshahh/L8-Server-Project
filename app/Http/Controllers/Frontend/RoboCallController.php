<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RoboCallController extends Controller
{
    /**
     * 🔥 Helper: Battery disconnect check
     */
    private function isBatteryDisconnect($text)
    {
        return stripos($text, 'battery') !== false
            && stripos($text, 'disconnect') !== false;
    }

    /**
     * 🔥 Helper: Alert signature (future dedupe support)
     */
    private function getAlertSignature($deviceId, $alertId, $message)
    {
        return md5($deviceId . $alertId . $message);
    }

public function webhook(Request $request)
{
    $data = $request->all();

    Log::info("Webhook received", $data);

    if (empty($data['rc_id'])) {
        Log::warning("Webhook without rc_id", $data);

        return response()->json([
            'status' => 'error',
            'message' => 'rc_id required'
        ], 400);
    }

    $status = $data['status'] ?? $data['call_status'] ?? 'unknown';

    try {

        $updated = DB::table('hypegps_web.robo_calls')
            ->where('rc_id', $data['rc_id'])
            ->update([
                'status'       => $status,
                'call_status'  => $status,
                'dtmf'         => $data['dtmf'] ?? null,
                'decoded_response' => json_encode($data),
                'updated_at'   => now(),
            ]);

        Log::info("Webhook Updated", [
            'rc_id'   => $data['rc_id'],
            'status'  => $status,
            'updated' => $updated,
        ]);

        return response()->json([
            'status'  => 'success',
            'updated' => $updated
        ]);

    } catch (\Exception $e) {

        Log::error("Webhook Error", [
            'error' => $e->getMessage(),
            'data'  => $data
        ]);

        return response()->json([
            'status' => 'error'
        ], 500);
    }
}

    public function getRoboCallUrl()
    {
        $data = DB::table('hypegps_web.robo_call_api_url')->find(1);

        return response()->json([
            'link' => $data->link ?? null,
        ]);
    }

    public function updateRoboCallUrl(Request $request)
    {
        $request->validate([
            'link' => 'required|url',
        ]);

        $url = rtrim($request->input('link'), '/');

        $updated = DB::table('hypegps_web.robo_call_api_url')
            ->where('id', 1)
            ->update(['link' => $url]);

        return response()->json([
            'success' => (bool) $updated,
            'message' => $updated ? 'API URL updated successfully' : 'No changes made'
        ]);
    }

    public function clearQueue()
    {
        DB::table('hypegps_web.robo_call_queue')
            ->where('status', 'completed')
            ->delete();

        return response()->json([
            'message' => 'All completed calls cleared successfully'
        ]);
    }

    public function getLastFeedback()
    {
        $last = DB::table('hypegps_web.robo_calls')
            ->where('type', 'Feedback Call')
            ->orderBy('created_at', 'desc')
            ->first();

        return response()->json([
            'last_feedback_time' => $last
                ? Carbon::parse($last->created_at)->format('d-m-Y')
                : null
        ]);
    }

    public function showModal(Request $request)
    {
        $userId = auth()->id();

        $calls = DB::table('hypegps_web.robo_calls as c')
            ->leftJoin('hypegps_web.users as u', 'c.user_id', '=', 'u.id')
            ->leftJoin('hypegps_web.devices as d', 'c.device_id', '=', 'd.id')
            ->whereExists(function ($q) use ($userId) {
                $q->select(DB::raw(1))
                    ->from('hypegps_web.user_device_pivot')
                    ->whereColumn('hypegps_web.user_device_pivot.device_id', 'c.device_id')
                    ->where('hypegps_web.user_device_pivot.user_id', $userId);
            })
            ->select([
                'c.*',
                'u.phone_number',
                'u.username',
                'd.name as device_name',
            ]);

        $queue = DB::table('hypegps_web.robo_call_queue as q')
            ->leftJoin('hypegps_web.users as u', 'q.user_id', '=', 'u.id')
            ->leftJoin('hypegps_web.devices as d', 'q.device_id', '=', 'd.id')
            ->where('q.status', 'pending')
            ->select([
                'q.rc_id',
                'q.call_to',
                'q.status as queue_status',
                'u.phone_number',
                'u.username',
                'd.name as device_name',
            ]);

        $data = $calls->unionAll($queue)
            ->orderByDesc('rc_id')
            ->get();

        if ($request->ajax()) {
            return view('front::Robocall.status', compact('data'))->render();
        }

        return view('front::Robocall.status', compact('data'));
    }

    public function getMessage(Request $request)
    {
        $client = new Client();
        $page = $request->query('page', 1);

        $response = $client->request('GET', 'https://sys.wasms.net/api/get/wa.sent', [
            'query' => [
                'secret' => 'acc469a1d5e090b7ed350c6dbee4f1ded5a2e138',
                'page' => $page
            ],
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

        return response()->json([
            'page' => $page,
            'data' => json_decode($response->getBody(), true)
        ]);
    }
}