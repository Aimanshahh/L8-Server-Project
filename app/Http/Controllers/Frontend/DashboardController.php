<?php namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tobuli\Helpers\Dashboard\DashboardManager;

class DashboardController extends Controller
{
    /**
     * @var DashboardManager
     */
    private $dashboardManager;

    public function __construct(DashboardManager $dashboardManager)
    {
        parent::__construct();

        $this->dashboardManager = $dashboardManager;
    }

    public function index(Request $request)
    {
        $user = $this->user;

        // Devices with their latest position + type
        $devices = $user->devices()->with(['traccar', 'deviceType'])->get();
        $total   = $devices->count();

        // Per-status id sets (same scopes the old over_view block used)
        $idSets = [
            'moving' => $user->devices()->move()->pluck('id')->all(),
            'idle'   => $user->devices()->idle()->pluck('id')->all(),
            'parked' => $user->devices()->park()->pluck('id')->all(),
            'offline'=> $user->devices()->offline()->pluck('id')->all(),
            'never'  => $user->devices()->neverConnected()->pluck('id')->all(),
        ];

        $statusCounts = ['moving' => 0, 'idle' => 0, 'offline' => 0];
        $statusOf     = [];
        $markers      = [];

        foreach ($devices as $device) {
            if (in_array($device->id, $idSets['moving'], true))
                $bucket = 'moving';
            elseif (in_array($device->id, $idSets['idle'], true) || in_array($device->id, $idSets['parked'], true))
                $bucket = 'idle';
            else
                $bucket = 'offline';

            $statusCounts[$bucket]++;
            $statusOf[$device->id] = $bucket;

            if ($device->lat && $device->lng) {
                $markers[] = [
                    'id'     => $device->id,
                    'name'   => $device->name,
                    'lat'    => (float) $device->lat,
                    'lng'    => (float) $device->lng,
                    'status' => $bucket,
                ];
            }
        }

        $pct = function ($count) use ($total) {
            return $total ? round($count / $total * 100, 1) : 0;
        };

        $donutColors = ['moving' => '#388E3C', 'idle' => '#0288D1', 'offline' => '#E64A19'];
        $donut = [];
        $gradient = [];
        $cursor = 0;
        foreach (['moving' => 'Moving', 'idle' => 'Idle', 'offline' => 'Offline'] as $key => $label) {
            $p = $pct($statusCounts[$key]);
            $donut[] = [
                'label' => $label,
                'count' => $statusCounts[$key],
                'pct'   => $p,
                'color' => $donutColors[$key],
            ];
            if ($statusCounts[$key] > 0) {
                $from = $cursor; $cursor += $p; $to = $cursor;
                $gradient[] = $donutColors[$key] . ' ' . $from . '% ' . $to . '%';
            }
        }
        if (empty($gradient))
            $gradient[] = '#e2e8f0 0% 100%';

        $alertsCount = DB::table('events')->where('user_id', $user->id)->where('deleted', 0)->count();

        $events = DB::table('events')
            ->leftJoin('devices', 'devices.id', '=', 'events.device_id')
            ->where('events.user_id', $user->id)
            ->where('events.deleted', 0)
            ->orderBy('events.id', 'desc')
            ->limit(8)
            ->get(['events.*', 'devices.name as device_name']);

        $activity = $devices
            ->filter(function ($d) { return $d->traccar && $d->traccar->time; })
            ->sortByDesc(function ($d) { return $d->traccar->time; })
            ->take(8)
            ->values();

        $statusCards = [
            [
                'label'  => 'Total Devices',
                'value'  => $total,
                'color'  => '#1677c8',
                'icon'   => 'fa-car',
                'legend' => [
                    ['Moving', $statusCounts['moving'], '#388E3C'],
                    ['Idle',   $statusCounts['idle'],   '#0288D1'],
                    ['Offline', $statusCounts['offline'], '#E64A19'],
                ],
            ],
            ['label' => 'Currently Moving', 'value' => $statusCounts['moving'], 'color' => '#388E3C', 'pct' => $pct($statusCounts['moving']), 'icon' => 'fa-car-side'],
            ['label' => 'Currently Idle',   'value' => $statusCounts['idle'],   'color' => '#0288D1', 'pct' => $pct($statusCounts['idle']),   'icon' => 'fa-clock'],
            ['label' => 'Offline',          'value' => $statusCounts['offline'],'color' => '#E64A19', 'pct' => $pct($statusCounts['offline']), 'icon' => 'fa-power-off'],
            ['label' => 'Active Alerts',    'value' => $alertsCount,            'color' => '#f97316', 'icon' => 'fa-triangle-exclamation'],
        ];

        return view('front::Dashboard.index', [
            'statusCards' => $statusCards,
            'donut'       => $donut,
            'donutGradient'=> implode(', ', $gradient),
            'total'       => $total,
            'markers'     => $markers,
            'devices'     => $devices->take(6),
            'statusOf'    => $statusOf,
            'events'      => $events,
            'activity'    => $activity,
        ]);
    }

    public function blockContent()
    {
        $content = $this->dashboardManager->getContent(request('name'));

        if (is_null($content))
            return ['status' => 0];

        return response()->json(['status' => 1, 'html' => $content]);
    }

    public function updateConfig()
    {
        $config = request('dashboard');

        $this->user->setSettings('dashboard', $config, true);

        return response()->json(['status' => 1]);
    }
}
