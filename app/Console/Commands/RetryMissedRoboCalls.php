<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ModalHelpers\DeviceModalHelper;
use Tobuli\Services\DeviceConfigService;
use Tobuli\Services\CustomValuesService;


class RetryMissedRoboCalls extends Command
{
    protected $signature = 'robocall:retry-missed';
    protected $description = 'Retry Robo Call if its not routed';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            $this->processFallbacks();
        } catch (\Throwable $e) {
            Log::error('Robo-call fallback pass failed: ' . $e->getMessage());
        }

       $events = DB::table('hypegps_web.events')
        ->join('hypegps_web.alerts', 'hypegps_web.events.alert_id', '=', 'hypegps_web.alerts.id')
        ->join('hypegps_web.devices', 'hypegps_web.events.device_id', '=', 'hypegps_web.devices.id') // <- ADD THIS JOIN
        ->select(
            'hypegps_web.events.*',
            'hypegps_web.alerts.name as alert_name',
            'hypegps_web.alerts.notifications as alert_notifications',
            'hypegps_web.devices.name as device_name' // <- NOW THIS WILL WORK
        )
        ->where('hypegps_web.events.created_at', '>=', now()->subMinutes(5))
        ->whereNotNull('hypegps_web.events.alert_id')
        ->whereNotIn('hypegps_web.events.id', function ($query) {
            $query->select('event_id')
                  ->from('hypegps_web.robo_calls')
                  ->where('is_call_send', 1);
        })
        ->get();

        // 👉 FIX: Log in a system user so DeviceModalHelper constructor doesn't crash
        $systemUser = \Tobuli\Entities\User::first();
        if ($systemUser) {
            \Auth::login($systemUser);
        }

        // Log::info("📊 Found {$events->count()} eligible events for robocall retry.");

        foreach ($events as $event) {

            
            $existingCall = DB::table('hypegps_web.robo_calls')
                ->where('event_id', $event->id)
                ->latest('id')
                ->first();

            if ($existingCall && isset($existingCall->error_message)) {
                $lowerMessage = strtolower($existingCall->error_message);
                if (str_contains($lowerMessage, 'skipping robo call due to inactive')) {
                    // Log::info("⛔ Skipping Event ID: {$event->id} – inactive flag set.");
                    continue;
                }
            }

            
            $recentAttempts = DB::table('hypegps_web.robo_calls')
                ->where('event_id', $event->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            if ($recentAttempts >= 3) {
                // Log::warning("🔁 Too many retry attempts for Event ID: {$event->id}");
                continue;
            }

            
            $notifications = json_decode($event->alert_notifications, true);
            if (!is_array($notifications)) {
                // Log::error("🚫 Invalid notifications format for Event ID: {$event->id}");
                continue;
            }

            $roboCallEnabled = $notifications['robo_call']['active'] ?? false;

            if (!$roboCallEnabled) {
                // Log::info("🔇 RoboCall disabled in alert config for Event ID: {$event->id}");
                continue;
            }

            // Log::warning("📞 Retrying RoboCall for Event ID: {$event->id}");

            
            $additional = $this->safeJson($event->additional, $event->id);

            

            try {
                $structuredEvent = [
                    'id' => $event->id,
                    'device_id' => $event->device_id,
                    'device_name' => $event->device_name ?? 'Unknown',
                    'alert_id' => $event->alert_id,
                    'alert' => [
                        'notifications' => $notifications
                    ],
                    'created_at' => $event->created_at,
                    'type' => $event->type ?? 'unknown',
                    'name' => $event->message ?? 'unknown', 
                    'detail' => $event->message ?? '', 
                    'time' => $event->time ?? '',
                    'additional' => $additional,
                ];

                // Log::info("🧱 Structured Event built for Event ID: {$event->id}");
                $helper = new \ModalHelpers\DeviceModalHelper(
        app(\Tobuli\Services\DeviceConfigService::class),
        app(\Tobuli\Services\DeviceService::class),
        app(\Tobuli\Services\CustomValuesService::class),
        app(\Tobuli\Services\FractalTransformerService::class)
    );

                // Log::info("⚙️ Calling checkAndTriggerRoboCall()...");
                $helper->checkAndTriggerRoboCall([$structuredEvent]);
                // Log::info("✅ checkAndTriggerRoboCall finished for Event ID: {$event->id}");

            } catch (\Throwable $e) {
                Log::error("❌ Exception while retrying RoboCall for Event ID: {$event->id} – {$e->getMessage()}");
            }
        }
    }

    /**
     * Secondary-number fallback (primary + secondary only, never cycles).
     *
     * For calls this server placed (robo_calls rows): when the primary number
     * was not reached (webhook status no_answer / busy / failed / congestion),
     * call the user's secondary number once, `fallback_minutes` after the
     * primary call. Calls with no result after 15 minutes are skipped.
     */
    protected function processFallbacks()
    {
        $settings = DB::table('hypegps_web.robo_call_settings')->where('id', 1)->first();

        if (!$settings || (int) $settings->enabled !== 1) {
            return;
        }

        $delay = (int) $settings->fallback_minutes;
        $delay = $delay > 0 ? $delay : 5;
        $maxAge = 15;

        $notReached = \App\Services\Robocall\RobocallSettings::statusList('not_reached_statuses');
        $answered = \App\Services\Robocall\RobocallSettings::statusList('answered_statuses');

        $rows = DB::table('hypegps_web.robo_calls')
            ->where('is_call_send', 1)
            ->whereNotNull('event_id')
            ->whereNotNull('call_status')
            ->where('created_at', '<=', now()->subMinutes($delay))
            ->where('created_at', '>=', now()->subMinutes($maxAge))
            ->orderBy('id')
            ->get();

        $helper = null;

        foreach ($rows as $row) {
            try {
                $status = strtolower(str_replace(['-', ' '], '_', trim((string) $row->call_status)));

                if (!in_array($status, $notReached, true)) {
                    continue;
                }

                $user = DB::table('hypegps_web.users')->where('id', $row->user_id)->where('is_call_enabled', 1)->first();

                if (!$user) {
                    continue;
                }

                $numbers = \App\Services\Robocall\RobocallNumbers::forUser($user);

                // Only the primary call leads to a fallback, and only when a secondary exists.
                if (count($numbers) < 2 || $numbers[0] !== $row->call_to) {
                    continue;
                }

                $secondary = $numbers[1];

                $answeredAlready = DB::table('hypegps_web.robo_calls')
                    ->where('event_id', $row->event_id)
                    ->where('user_id', $row->user_id)
                    ->whereIn('call_status', $answered)
                    ->exists();

                if ($answeredAlready) {
                    continue;
                }

                // One attempt only, whatever happened to it.
                $alreadyTried = DB::table('hypegps_web.robo_calls')
                    ->where('event_id', $row->event_id)
                    ->where('call_to', $secondary)
                    ->exists();

                if ($alreadyTried) {
                    continue;
                }

                $event = DB::table('hypegps_web.events')
                    ->join('hypegps_web.alerts', 'hypegps_web.events.alert_id', '=', 'hypegps_web.alerts.id')
                    ->join('hypegps_web.devices', 'hypegps_web.events.device_id', '=', 'hypegps_web.devices.id')
                    ->select(
                        'hypegps_web.events.*',
                        'hypegps_web.alerts.notifications as alert_notifications',
                        'hypegps_web.devices.name as device_name'
                    )
                    ->where('hypegps_web.events.id', $row->event_id)
                    ->first();

                if (!$event) {
                    continue;
                }

                $notifications = json_decode($event->alert_notifications, true);

                if (!is_array($notifications) || empty($notifications['robo_call']['active'])) {
                    continue;
                }

                $structured = [
                    'id'          => $event->id,
                    'device_id'   => $event->device_id,
                    'device_name' => $event->device_name ?? 'Unknown',
                    'alert_id'    => $event->alert_id,
                    'alert'       => ['notifications' => $notifications],
                    'created_at'  => $event->created_at,
                    'type'        => $event->type ?? 'unknown',
                    'name'        => $event->message ?? 'unknown',
                    'detail'      => $event->message ?? '',
                    'time'        => $event->time ?? '',
                    'additional'  => $this->safeJson($event->additional, $event->id),
                ];

                if (!$helper) {
                    $systemUser = \Tobuli\Entities\User::first();
                    if ($systemUser) {
                        \Auth::login($systemUser);
                    }

                    $helper = new \ModalHelpers\DeviceModalHelper(
                        app(\Tobuli\Services\DeviceConfigService::class),
                        app(\Tobuli\Services\DeviceService::class),
                        app(\Tobuli\Services\CustomValuesService::class),
                        app(\Tobuli\Services\FractalTransformerService::class)
                    );
                }

                Log::info('Robo-call fallback: primary not reached, dialing secondary.', [
                    'event_id' => $row->event_id, 'user_id' => $row->user_id,
                    'primary' => $row->call_to, 'primary_status' => $status, 'secondary' => $secondary,
                ]);

                $helper->checkAndTriggerRoboCall([$structured], ['user_id' => (int) $row->user_id, 'number' => $secondary]);
            } catch (\Throwable $e) {
                Log::error('Robo-call fallback failed for robo_calls #' . $row->id . ': ' . $e->getMessage());
            }
        }

        // Primary calls that never reported a result: log once, no fallback.
        $silent = DB::table('hypegps_web.robo_calls')
            ->where('is_call_send', 1)
            ->whereNotNull('event_id')
            ->whereNull('call_status')
            ->where('created_at', '<', now()->subMinutes($maxAge))
            ->where('created_at', '>=', now()->subMinutes($maxAge + 10))
            ->get(['id', 'event_id', 'user_id', 'call_to']);

        foreach ($silent as $s) {
            if (\Illuminate\Support\Facades\Cache::add('rc_fb_nostatus_' . $s->id, 1, 3600)) {
                Log::warning('Robo-call: no call result received 15+ min after the call (fallback skipped).', (array) $s);
            }
        }
    }

    /**
     * Safely decode the 'additional' JSON field, or return array if already decoded
     */
    private function safeJson($raw, $eventId)
    {
        if (empty($raw)) {
            Log::info("ℹ️ [Event ID: $eventId] 'additional' is empty or null.");
            return [];
        }

        if (is_array($raw)) {
            Log::info("ℹ️ [Event ID: $eventId] 'additional' is already an array.");
            return $raw;
        }

        if (!is_string($raw)) {
            Log::warning("⚠️ [Event ID: $eventId] 'additional' is not a string. Skipping decode.");
            return [];
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("🚫 [Event ID: $eventId] Failed to decode 'additional': " . json_last_error_msg());
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

}
