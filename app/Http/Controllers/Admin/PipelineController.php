<?php namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class PipelineController extends BaseController
{
    /** how many minutes of write-throughput history the chart shows */
    const CHART_WINDOW_MINUTES = 30;
    /** window used for the "written in the last N minutes" totals */
    const COUNT_WINDOW_MINUTES = 60;
    /** how many devices appear in the recent positions list */
    const RECENT_LIMIT = 15;
    /** how many unregistered IMEIs appear in the log table */
    const UNREGISTERED_LIMIT = 8;

    public function index()
    {
        $section = 'pipeline';
        $data = $this->collect();

        return View::make('admin::Pipeline.index')->with(compact('section', 'data'));
    }

    public function data()
    {
        return Response::json($this->collect());
    }

    /**
     * Gather every metric the page and its refresh endpoint render.
     */
    protected function collect()
    {
        $throughput   = $this->throughput();
        $unregistered = $this->unregistered();
        $devices      = $this->devices();
        $recent       = $this->recentPositions();
        $health       = $this->health($throughput, $devices, $unregistered);
        $queue        = $this->queue();

        return [
            'generated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'throughput'   => $throughput,
            'unregistered' => $unregistered,
            'devices'      => $devices,
            'recent'       => $recent,
            'health'       => $health,
            'queue'        => $queue,
        ];
    }

    /**
     * Redis queue status for the position ingestion pipeline.
     */
    protected function queue()
    {
        $default = [
            'depth'     => 0,
            'keys'      => 0,
            'locks'     => 0,
            'workers'   => 0,
            'available' => false,
            'error'     => null,
            'top'       => [],
        ];

        try {
            $redis = Redis::connection();
            $redis->ping();

            $default['available'] = true;

            // Scan queue keys
            $keys = $redis->keys('*queue*');
            $default['keys'] = count($keys);

            $totalDepth = 0;
            $top = [];

            foreach ($keys as $key) {
                try {
                    $type = $redis->type($key);

                    // Only List / Sorted Set carry queue depth
                    if ($type === 'list') {
                        $depth = (int) $redis->llen($key);
                    } elseif ($type === 'zset') {
                        $depth = (int) $redis->zcard($key);
                    } else {
                        continue;
                    }

                    if ($depth <= 0) {
                        continue;
                    }

                    // Try to extract device id from the key (e.g. queues:positions:2295)
                    $deviceName = null;
                    $imei = '—';

                    if (preg_match('/(\d{3,})$/', $key, $m)) {
                        $deviceId = (int) $m[1];
                        $device = DB::table('devices')->find($deviceId);

                        if ($device) {
                            $deviceName = $device->name;
                            $imei       = $device->imei;
                        }
                    }

                    $top[] = [
                        'name'  => $deviceName,
                        'imei'  => $imei,
                        'depth' => $depth,
                        'key'   => $key,
                    ];

                    $totalDepth += $depth;
                } catch (\Throwable $e) {
                    // Skip individual key errors
                    continue;
                }
            }

            // Sort by depth desc, keep top 20
            usort($top, function ($a, $b) {
                return $b['depth'] <=> $a['depth'];
            });

            $default['top']   = array_slice($top, 0, 20);
            $default['depth'] = $totalDepth;

            // Locks — count keys that look like locks
            $lockKeys = $redis->keys('*lock*');
            $default['locks'] = count($lockKeys);

            // Workers — count running artisan insert:run processes
            $workers = 0;
            $psOutput = @shell_exec("ps aux | grep -c '[i]nsert:run'");
            if ($psOutput !== null) {
                $workers = (int) trim((string) $psOutput);
            }
            $default['workers'] = $workers;
        } catch (\Throwable $e) {
            $default['error'] = $e->getMessage();
        }

        return $default;
    }

    /**
     * Positions persisted per minute, counted from tc_positions.
     */
    protected function throughput()
    {
        $result = [
            'error'      => null,
            'tables'     => 1,
            'per_minute' => [],
            'max'        => 0,
            'last_5m'    => 0,
            'last_60m'   => 0,
            'rate'       => 0,
        ];

        try {
            $from = Carbon::now()->subMinutes(self::COUNT_WINDOW_MINUTES)->startOfMinute();

            $rows = DB::connection('traccar_mysql')
                ->table('tc_positions')
                ->select(DB::raw("DATE_FORMAT(servertime, '%Y-%m-%d %H:%i') AS m, COUNT(*) AS c"))
                ->where('servertime', '>=', $from->format('Y-m-d H:i:s'))
                ->groupBy('m')
                ->get();

            $counts = [];
            foreach ($rows as $row) {
                $counts[$row->m] = (int) $row->c;
            }

            $now = Carbon::now();
            $perMinute = [];
            $last5 = 0;
            $last60 = 0;
            $max = 0;

            for ($i = self::CHART_WINDOW_MINUTES - 1; $i >= 0; $i--) {
                $at = $now->copy()->subMinutes($i);
                $count = isset($counts[$at->format('Y-m-d H:i')]) ? $counts[$at->format('Y-m-d H:i')] : 0;

                $perMinute[] = [
                    'label' => $at->format('H:i'),
                    'count' => $count,
                ];

                $max = max($max, $count);

                if ($i < 5) {
                    $last5 += $count;
                }
            }

            foreach ($counts as $count) {
                $last60 += $count;
            }

            $result['per_minute'] = $perMinute;
            $result['max']        = $max;
            $result['last_5m']    = $last5;
            $result['last_60m']   = $last60;
            $result['rate']       = round($last5 / 5, 2);
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Unknown devices that sent data but have no device record.
     */
    protected function unregistered()
    {
        $rows = DB::table('unregistered_devices_log');

        $recent = (clone $rows)
            ->orderBy('date', 'desc')
            ->limit(self::UNREGISTERED_LIMIT)
            ->get(['imei', 'port', 'ip', 'date', 'times']);

        return [
            'rows'     => (clone $rows)->count(),
            'attempts' => (int) (clone $rows)->sum('times'),
            'today'    => (clone $rows)->where('date', '>=', Carbon::today())->count(),
            'recent'   => $recent,
        ];
    }

    /**
     * Device connectivity derived from tc_devices.lastupdate.
     */
    protected function devices()
    {
        $timeout     = $this->onlineTimeout();
        $onlineSince = Carbon::now()->subSeconds($timeout);

        $total = DB::table('devices')->count();

        $tcIds = DB::connection('traccar_mysql')->table('tc_devices')->pluck('id');

        $onlineIds = DB::connection('traccar_mysql')
            ->table('tc_devices')
            ->where('lastupdate', '>=', $onlineSince->format('Y-m-d H:i:s'))
            ->pluck('id');

        $online = DB::table('devices')
            ->whereIn('traccar_device_id', $onlineIds)
            ->count();

        $connected = DB::table('devices')
            ->whereIn('traccar_device_id', $tcIds)
            ->count();

        return [
            'total'   => $total,
            'online'  => $online,
            'offline' => max(0, $total - $online),
            'never'   => max(0, $total - $connected),
            'timeout' => $timeout,
        ];
    }

    /**
     * Latest position each device reported.
     */
    protected function recentPositions()
    {
        $onlineSince = Carbon::now()->subSeconds($this->onlineTimeout());

        $tcDevices = DB::connection('traccar_mysql')
            ->table('tc_devices')
            ->whereNotNull('lastupdate')
            ->orderBy('lastupdate', 'desc')
            ->limit(self::RECENT_LIMIT)
            ->get(['id', 'uniqueid', 'lastupdate', 'positionid']);

        if ($tcDevices->isEmpty()) {
            return [];
        }

        $tcDeviceIds = $tcDevices->pluck('id')->all();
        $positionIds = $tcDevices->pluck('positionid')->filter()->all();

        $webDevices = DB::table('devices')
            ->whereIn('traccar_device_id', $tcDeviceIds)
            ->get(['id', 'name', 'imei', 'traccar_device_id'])
            ->keyBy('traccar_device_id');

        $positions = DB::connection('traccar_mysql')
            ->table('tc_positions')
            ->whereIn('id', $positionIds)
            ->get(['id', 'protocol', 'address', 'speed', 'latitude', 'longitude', 'servertime', 'fixtime'])
            ->keyBy('id');

        $result = [];

        foreach ($tcDevices as $tc) {
            $device   = $webDevices->get($tc->id);
            $position = $tc->positionid ? $positions->get($tc->positionid) : null;

            $serverTime = $tc->lastupdate;
            $age = $serverTime
                ? max(0, Carbon::now()->diffInSeconds(Carbon::parse($serverTime)))
                : null;

            $result[] = [
                'id'          => $device->id ?? null,
                'name'        => $device->name ?? $tc->uniqueid,
                'imei'        => $device->imei ?? $tc->uniqueid,
                'server_time' => $serverTime,
                'time'        => $position ? $position->fixtime : $serverTime,
                'protocol'    => $position ? $position->protocol : null,
                'address'     => $position ? $position->address : null,
                'speed'       => $position ? $position->speed : null,
                'online'      => $serverTime && Carbon::parse($serverTime)->gte($onlineSince),
                'age'         => $age,
                'age_human'   => $age === null ? null : $this->humanAge($age),
            ];
        }

        return $result;
    }

    /**
     * Roll the raw metrics into a single status the toolbar can show.
     */
    protected function health($throughput, $devices, $unregistered)
    {
        $issues = [];
        $level = 'ok';

        $escalate = function ($to) use (&$level) {
            $rank = ['ok' => 0, 'warning' => 1, 'critical' => 2];
            if ($rank[$to] > $rank[$level]) {
                $level = $to;
            }
        };

        if ($throughput['error']) {
            $escalate('critical');
            $issues[] = 'Could not read tc_positions: ' . $throughput['error'];
        } elseif ($throughput['rate'] <= 0 && $throughput['last_60m'] <= 0) {
            $escalate('warning');
            $issues[] = 'No positions written to tc_positions in the last 60 minutes. Check that Traccar is receiving data.';
        }

        if ($unregistered['today'] > 0) {
            $issues[] = $unregistered['today'] . ' unknown IMEI(s) tried to connect today.';
        }

        if ($devices['never'] > 0) {
            $issues[] = $devices['never'] . ' device(s) have never reported a position.';
        }

        return [
            'level'  => $level,
            'issues' => $issues,
        ];
    }

    protected function onlineTimeout()
    {
        return (int) settings('main_settings.default_object_online_timeout') * 60;
    }

    protected function humanAge($seconds)
    {
        if ($seconds < 60) {
            return $seconds . 's ago';
        }

        if ($seconds < 3600) {
            return floor($seconds / 60) . 'm ago';
        }

        if ($seconds < 86400) {
            return floor($seconds / 3600) . 'h ago';
        }

        return floor($seconds / 86400) . 'd ago';
    }
}