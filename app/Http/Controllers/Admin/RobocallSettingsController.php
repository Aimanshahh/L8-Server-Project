<?php namespace App\Http\Controllers\Admin;

use App\Services\Robocall\RobocallSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class RobocallSettingsController extends BaseController
{
    function __construct()
    {
        parent::__construct();
    }

    /** The route group already requires auth.admin; this is a second lock. */
    protected function guard()
    {
        $user = Auth::user();

        if (!$user || !$user->isAdmin()) {
            abort(403);
        }
    }

    public function index()
    {
        $this->guard();

        return View::make('admin::RobocallSettings.index', ['s' => RobocallSettings::forAdmin()]);
    }

    public function store(Request $request)
    {
        $this->guard();

        $v = Validator::make($request->all(), [
            'enabled'                 => 'required|in:0,1',
            'api_name'                => 'nullable|string|max:45',
            'api_url'                 => ['required', 'url', 'max:500', 'regex:/^https:\/\//i'],
            'api_key'                 => 'nullable|string|max:500',
            'amount'                  => 'required|integer|min:1|max:1000000',
            'fallback_minutes'        => 'required|integer|min:1|max:60',
            'cooldown_hours'          => 'required|integer|min:1|max:720',
            'geofence_cooldown_hours' => 'required|integer|min:1|max:720',
            'answered_statuses'       => 'required|string|max:255',
            'not_reached_statuses'    => 'required|string|max:255',
        ], [
            'api_url.regex' => 'The API URL must start with https://',
        ]);

        $answered   = RobocallSettings::normalizeStatuses($request->input('answered_statuses'));
        $notReached = RobocallSettings::normalizeStatuses($request->input('not_reached_statuses'));

        $v->after(function ($validator) use ($answered, $notReached) {
            if (!$answered) {
                $validator->errors()->add('answered_statuses', 'Enter at least one answered status word.');
            }

            if (!$notReached) {
                $validator->errors()->add('not_reached_statuses', 'Enter at least one not-reached status word.');
            }

            if (array_intersect($answered, $notReached)) {
                $validator->errors()->add('not_reached_statuses', 'A status word cannot be in both lists.');
            }
        });

        if ($v->fails()) {
            return response()->json(['ok' => false, 'errors' => $v->errors()->all()]);
        }

        $key  = trim((string) $request->input('api_key', ''));
        $name = trim((string) $request->input('api_name', '')) ?: 'Robocall';
        $url  = trim((string) $request->input('api_url'));

        $row = [
            'enabled'                 => (int) $request->input('enabled'),
            'amount'                  => (int) $request->input('amount'),
            'fallback_minutes'        => (int) $request->input('fallback_minutes'),
            'cooldown_hours'          => (int) $request->input('cooldown_hours'),
            'geofence_cooldown_hours' => (int) $request->input('geofence_cooldown_hours'),
            'answered_statuses'       => implode(',', $answered),
            'not_reached_statuses'    => implode(',', $notReached),
            'updated_at'              => now(),
        ];

        if ($key !== '') {
            $row['api_key'] = Crypt::encryptString($key);
        }

        try {
            DB::transaction(function () use ($row, $name, $url) {
                DB::table('hypegps_web.robo_call_settings')->updateOrInsert(['id' => 1], $row);
                DB::table('hypegps_web.robo_call_api_url')
                    ->updateOrInsert(['id' => 1], ['api_name' => $name, 'link' => $url, 'updated_at' => now()]);
            });
        } catch (\Throwable $e) {
            \Log::error('Robocall settings save failed: ' . $e->getMessage());

            return response()->json(['ok' => false, 'errors' => ['Save failed. Check storage/logs/laravel.log.']]);
        }

        \Log::info('Robocall settings updated', [
            'by' => Auth::id(), 'enabled' => $row['enabled'], 'api_url' => $url, 'api_key_changed' => $key !== '',
        ]);

        return response()->json(['ok' => true]);
    }

    public function testCall(Request $request)
    {
        $this->guard();

        $number = preg_replace('/[\s\-+]/', '', (string) $request->input('number'));
        $voice  = trim((string) $request->input('voice_id'));

        if (!preg_match('/^\d{10,15}$/', $number) || !preg_match('/^[1-9]\d{0,3}$/', $voice)) {
            return response()->json(['ok' => false, 'errors' => ['Enter a valid number (digits only, e.g. 03XXXXXXXXX) and a numeric Voice ID.']]);
        }

        $url = RobocallSettings::url();
        $key = RobocallSettings::apiKey();

        if (!$url || !$key) {
            return response()->json(['ok' => false, 'errors' => ['The API URL or API key is not configured. Save the settings first.']]);
        }

        if (!Cache::add('robocall_test_call_lock', 1, 30)) {
            return response()->json(['ok' => false, 'errors' => ['Please wait 30 seconds between test calls.']]);
        }

        try {
            $client = new \GuzzleHttp\Client(['connect_timeout' => 10, 'timeout' => 25, 'http_errors' => false]);
            $response = $client->get($url, ['query' => [
                'api_key'   => $key,
                'caller_id' => $number,
                'amount'    => (int) RobocallSettings::get()['amount'],
                'voice_id'  => $voice,
                'text1'     => 'Test call',
            ]]);

            \Log::info('Robocall test call placed', ['by' => Auth::id(), 'to' => $number, 'http_status' => $response->getStatusCode()]);

            return response()->json([
                'ok'          => true,
                'http_status' => $response->getStatusCode(),
                'body'        => mb_substr((string) $response->getBody(), 0, 2000),
            ]);
        } catch (\Throwable $e) {
            // the exception text can contain the request URL, which includes the API key
            $safe = str_replace($key, '***', $e->getMessage());
            \Log::error('Robocall test call failed: ' . $safe);

            return response()->json(['ok' => false, 'errors' => ['Request failed: ' . $safe]]);
        }
    }
}
