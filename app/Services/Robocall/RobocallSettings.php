<?php namespace App\Services\Robocall;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Robocall provider/behaviour settings, edited at Admin > Setup > Robocall settings.
 * Every reader fails safe: unreadable settings mean the current defaults.
 */
class RobocallSettings
{
    const DEFAULTS = [
        'enabled'                 => 0,
        'fallback_minutes'        => 5,
        'amount'                  => 1000,
        'cooldown_hours'          => 8,
        'geofence_cooldown_hours' => 24,
        'answered_statuses'       => 'completed,call_completed,answered',
        'not_reached_statuses'    => 'no_answer,busy,failed,congestion',
    ];

    protected static function row()
    {
        try {
            return DB::table('hypegps_web.robo_call_settings')->where('id', 1)->first();
        } catch (\Throwable $e) {
            \Log::error('Robocall settings unreadable, using defaults: ' . $e->getMessage());
            return null;
        }
    }

    public static function get()
    {
        $row = static::row();
        $out = static::DEFAULTS;

        if (!$row) {
            return $out;
        }

        foreach (static::DEFAULTS as $key => $default) {
            if (isset($row->$key) && $row->$key !== '') {
                $out[$key] = $row->$key;
            }
        }

        return $out;
    }

    public static function apiRow()
    {
        try {
            return DB::table('hypegps_web.robo_call_api_url')->find(1);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function url()
    {
        $api = static::apiRow();

        return $api && !empty($api->link) ? $api->link : null;
    }

    /** API key: encrypted DB value first, then .env (VOICE_API_KEY) as the fallback. */
    public static function apiKey()
    {
        try {
            $row = static::row();

            if ($row && !empty($row->api_key)) {
                return Crypt::decryptString($row->api_key);
            }
        } catch (\Throwable $e) {
            \Log::error('Robocall API key could not be decrypted, falling back to .env');
        }

        return env('VOICE_API_KEY');
    }

    /** Comma/newline separated words to a clean lowercase list. No other rewriting. */
    public static function normalizeStatuses($raw)
    {
        $out = [];

        foreach (preg_split('/[,;\n]+/', (string) $raw) as $part) {
            $s = strtolower(trim($part));

            if ($s !== '' && !in_array($s, $out, true)) {
                $out[] = $s;
            }
        }

        return $out;
    }

    public static function statusList($key)
    {
        return static::normalizeStatuses(static::get()[$key] ?? '');
    }

    /** Values for the settings page. Never includes the API key. */
    public static function forAdmin()
    {
        $cfg = static::get();
        $row = static::row();
        $api = static::apiRow();

        $source = ($row && !empty($row->api_key)) ? 'database' : (env('VOICE_API_KEY') ? 'env' : 'none');

        return [
            'enabled'                 => (int) $cfg['enabled'] === 1,
            'api_name'                => $api->api_name ?? 'Robocall',
            'api_url'                 => $api->link ?? '',
            'key_source'              => $source,
            'amount'                  => (int) $cfg['amount'],
            'fallback_minutes'        => (int) $cfg['fallback_minutes'],
            'cooldown_hours'          => (int) $cfg['cooldown_hours'],
            'geofence_cooldown_hours' => (int) $cfg['geofence_cooldown_hours'],
            'answered_statuses'       => implode(', ', static::normalizeStatuses($cfg['answered_statuses'])),
            'not_reached_statuses'    => implode(', ', static::normalizeStatuses($cfg['not_reached_statuses'])),
            'webhook_url'             => route('robocall.webhook'),
        ];
    }
}
