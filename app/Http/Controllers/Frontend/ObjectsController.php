<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\PermissionException;
use App\Http\Controllers\Controller;
use App\Transformers\Device\DeviceMapFullTransformer;
use App\Transformers\Device\DeviceMapTransformer;
use CustomFacades\ModalHelpers\DeviceModalHelper;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Server;
use Formatter;
use FractalTransformer;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use ModalHelpers\SendCommandModalHelper;
use Tobuli\Services\UserOpenGroupService;
use Tobuli\Helpers\Dashboard\DashboardManager;

class ObjectsController extends Controller {

    const LOAD_LIMIT = 30;

    private $dashboardManager;

    function __construct(DashboardManager $dashboardManager)
    {
        parent::__construct();

        Server::setMemoryLimit(config('server.device_memory_limit'));
        $this->dashboardManager = $dashboardManager;
    }

    public function index()
    {
        $user = $this->user;
        $blocks = [];
        $settings = getUserDashboardSettings($this->user);
	    foreach ($settings['blocks'] as $block => $config) {
            if ( ! $config['enabled'])
                continue;

            $blocks[$block] = $this->dashboardManager->getContent($block, false);
        }
        $version = Config::get('tobuli.version');
        $devices = [];

        $history = [
            'start' => Formatter::time()->convert(date('Y-m-d H:i:s'), 'Y-m-d'),
            'end' => Formatter::time()->convert(date('Y-m-d H:i:s'), 'Y-m-d'),
            'end_time' => '23:45',
        ];

        $dashboard = $this->user->getSettings('dashboard.enabled');

        return view('front::Objects.index')
            ->with(compact('devices', 'history', 'version', 'dashboard', 'blocks', 'user'));
    }

    public function items() {
        if ( ! $this->user->perm('devices', 'view') )
            throw new PermissionException();

        $full   = request()->get('full');
        $cursor = request()->get('cursor');

        $devices = $this->user
            ->devices()
            ->filter(request()->all())
            ->when(!$full, function($query) {
                $query->visible();
            })
            ->clearOrdersBy()
            //optizime first page load
            ->when(!$cursor, function($query) {
                $query->where('devices.id', '>', 0);
            })
            ->cursorPaginate(500);

        $transformer = $full ? DeviceMapFullTransformer::class : DeviceMapTransformer::class;

        return response()->json(
            FractalTransformer::cursorPaginate($devices, $transformer)->toArray()
        );
    }

    public function itemsSimple() {

        $deviceCollection = $this->user->devices()
            ->search(Request::input('search_phrase'))
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('front::Objects.itemsSimple')->with(compact('deviceCollection'));
    }

    public function itemsJson() {
        $data = DeviceModalHelper::itemsJson();

        return $data;
    }

    public function changeGroupStatus()
    {
        if (isDemoUser()) {
            return;
        }

        $id = $this->data['id'];

        (new UserOpenGroupService($this->user->deviceGroups()))
            ->changeStatus($id);
    }

    public function changeAlarmStatus()
    {
        if (!array_key_exists('id', $this->data) && array_key_exists('device_id', $this->data))
            $this->data['id'] = $this->data['device_id'];
        $item = DeviceRepo::find($this->data['id']);
        if (empty($item) || (!$item->users->contains($this->user->id) && !isAdmin()))
            return ['status' => 0];

        // New Traccar: tc_positions.fixtime (was positions.time)
        $position = $item->positions()->orderBy('fixtime', 'desc')->first();

        $sendCommandModalHelper = new SendCommandModalHelper();
        $sendCommandModalHelper->setData([
            'device_id' => $item->id,
            'type' => $item->alarm == 0 ? 'alarmArm' : 'alarmDisarm'
        ]);
        $result = $sendCommandModalHelper->gprsCreate();

        $alarm = $item->alarm;

        if ($result['status'] == 1) {
            $tr = TRUE;
            $times = 1;
            $val = '';
            if (isset($position)) {
                while($tr && $times < 6) {
                    // New Traccar: tc_positions.fixtime
                    $positions = $item->positions()->where('fixtime', '>', $position->time)->orderBy('fixtime', 'asc')->get();
                    if ($times >= 5)
                        $positions = $item->positions()->select('attributes')->orderBy('fixtime', 'desc')->limit(2)->get();
                    foreach ($positions as $pos) {
                        // New Traccar: alarm lives in attributes JSON. Use getParameter().
                        $alarmValue = $pos->getParameter('alarm');
                        if (empty($alarmValue))
                            continue;

                        $val = $alarmValue;
                        if ($val == 'lt' || $val == 'mt' || $val == 'lf') {
                            $tr = FALSE;
                            break;
                        }
                    }

                    $times++;
                    sleep(1);
                }
            }

            $status = 0;

            if (!$tr) {
                if (($item->alarm == 0 && $val == 'lt') || ($item->alarm == 1 && $val == 'mt')) {
                    $status = 1;
                    $alarm = $item->alarm == 1 ? 0 : 1;
                    DeviceRepo::update($item->id, [
                        'alarm' => $alarm
                    ]);
                }
            }

            return ['status' => $status, 'alarm' => intval($alarm), 'error' => trans('front.unexpected_error')];
        }
        else {
            return ['status' => 0, 'alarm' => intval($alarm), 'error' => isset($result['error']) ? $result['error'] : ''];
        }
    }

    public function alarmPosition()
    {
        $item = DeviceRepo::find($this->data['id']);
        if (empty($item) || (!$item->users->contains($this->user->id) && !isAdmin()))
            return response()->json(['status' => 0]);

        $sendCommandModalHelper = new SendCommandModalHelper();
        $sendCommandModalHelper->setData([
            'device_id' => $item->id,
            'type' => 'positionSingle'
        ]);
        $result = $sendCommandModalHelper->gprsCreate();

        if ($result['status'] == 1)
            return ['status' => 1];
        else
            return ['status' => 0, 'error' => isset($result['error']) ? $result['error'] : ''];
    }

    /**
     * Resolve a device's current address for list rendering.
     * Prefers Traccar's stored address; falls back to GPSWOX geocoder.
     */
    public static function resolveDeviceAddress(\Tobuli\Entities\Device $device): string
    {
        if ($device->traccar) {
            $position = $device->traccar->latestPosition;
            if ($position && !empty($position->address)) {
                return $position->address;
            }
        }

        $lat = $device->lat;
        $lng = $device->lng;

        if (empty($lat) || empty($lng)) {
            return '-';
        }

        try {
            return app(\Tobuli\Helpers\GeoLocation\GeoLocation::class)
                ->resolveAddress($lat, $lng) ?: '-';
        } catch (\Throwable $e) {
            return '-';
        }
    }
}
