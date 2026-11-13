<?php namespace App\Http\Controllers\Frontend;

use App\Exceptions\PermissionException;
use App\Exceptions\ResourseNotFoundException;
use App\Http\Controllers\Controller;
use App\Transformers\Device\DeviceMapTransformer;
use CustomFacades\ModalHelpers\DeviceModalHelper;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\DeviceImageValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Session;
use Tobuli\Entities\Device;
use Tobuli\Entities\Geofence;
use Tobuli\Exceptions\ValidationException;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\History\Actions\GroupGeofenceInOut;
use Tobuli\History\DeviceHistory;
use Tobuli\Services\DeviceImageService;
use Tobuli\Services\DeviceService;
use FractalTransformer;
use Tobuli\Services\EntityLoader\UserDevicesLoader;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;

class DevicesController extends Controller
{
    private $deviceService;

    public function __construct(DeviceService $deviceService)
    {
        parent::__construct();
        $this->deviceService = $deviceService;
    }

    protected function afterAuth($user)
    {
        $this->devicesLoader = new UserDevicesLoader($user);
        $this->devicesLoader->setRequestKey('devices');
    }

    public function index()
    {
        if (!$this->user->perm('devices', 'view'))
            throw new PermissionException();

        $items = $this->devicesLoader->get();

        return response()->json($items);
    }

    public function create()
    {
        $data = DeviceModalHelper::createData();
        return is_array($data) && !$this->api ? view('front::Devices.create')->with($data) : $data;
    }

    public function store()
    {
        if ($this->user->perm('custom_device_add', 'view'))
            throw new PermissionException();

        return DeviceModalHelper::create();
    }

    public function edit($id = NULL, $admin = FALSE) {
        $data = DeviceModalHelper::editData();

        $view = $data['item']->isBeacon() ? 'front::Beacons.edit' : 'front::Devices.edit';

        return is_array($data) && !$this->api
            ? view($view)->with(array_merge($data, ['admin' => $admin]))
            : $data;
    }

    public function update()
    {
        return DeviceModalHelper::edit();
    }

    public function resetAppUuid(int $id)
    {
        return DeviceModalHelper::resetAppUuid($id);
    }

    public function doResetAppUuid(int $id): RedirectResponse
    {
        $response = DeviceModalHelper::resetAppUuid($id);

        if ($response['status'] ?? 0) {
            Session::flash('messages', [trans('global.success')]);
        }

        return redirect()->back();
    }

    public function changeActive()
    {
        return DeviceModalHelper::changeActive();
    }

        public function destroy()
    {
        $deviceId = request('id') ?? request('device_id');
        if (!$deviceId) {
            return ['status' => 0, 'errors' => ['message' => 'Device ID missing']];
        }

        try {
            $device = \DB::table('hypegps_web.devices')->where('id', $deviceId)->first();
            if ($device) {
                $traccarId = $device->traccar_device_id;
                
                // Delete from hypegps_web
                \DB::table('hypegps_web.user_device_pivot')->where('device_id', $deviceId)->delete();
                \DB::table('hypegps_web.alert_device')->where('device_id', $deviceId)->delete();
                \DB::table('hypegps_web.devices')->where('id', $deviceId)->delete();

                // Delete from hypegps_traccar if traccarId exists
                if ($traccarId) {
                    \DB::table('hypegps_traccar.tc_user_device')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_device_notification')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_device_geofence')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_device_driver')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_device_command')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_device_attribute')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_positions')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_events')->where('deviceid', $traccarId)->delete();
                    \DB::table('hypegps_traccar.tc_devices')->where('id', $traccarId)->delete();
                }
            }
            return ['status' => 1, 'id' => $deviceId, 'deleted' => 1];
        } catch (\Exception $e) {
            return ['status' => 0, 'errors' => ['message' => $e->getMessage()]];
        }
    }

    public function doDestroy($id)
    {
        return view('front::Devices.destroy', compact('id'));
    }

    public function detach() {
        return DeviceModalHelper::detach();
    }

    public function stopTime($device_id = NULL)
    {
        if (is_null($device_id))
            $device_id = request()->get('device_id');

        $device = DeviceRepo::getWithFirst(['traccar', 'users', 'sensors'], ['id' => $device_id]);

        $this->checkException('devices', 'show', $device);

        return ['time' => $device->stopDuration];
    }

    public function followMap($device_id)
    {
        $item = Device::find($device_id);

        $this->checkException('devices', 'show', $item);

        return view('front::Devices.follow_map', [
            'item' => FractalTransformer::setSerializer(WithoutDataArraySerializer::class)
                ->item($item, DeviceMapTransformer::class)->toArray()
        ]);
    }

    public function inGeofences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|integer',
        ]);

        $this->checkException('devices', 'own', $device = Device::find($request->device_id));

        if ($validator->fails())
            return response()->json(['status' => 0, 'errors' => $validator->errors()]);

        if (!$this->user->geofences()->count())
            throw new ResourseNotFoundException(trans('front.geofences'));

        return response()->json([
            'status' => 1,
            'zones'  => $this->user->geofences()->containPoint($device->lat, $device->lng)->pluck('name')->all()
        ]);
    }

    public function wasInGeofence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from'        => 'required|date',
            'to'          => 'required|date',
            'device_id'   => 'required|integer',
            'geofence_id' => 'required|integer',
        ]);

        $this->checkException('devices', 'own', $device = Device::find($request->device_id));

        if ($validator->fails())
            return response()->json(['status' => 0, 'errors' => $validator->errors()]);

        if (is_null($geofence = $this->user->geofences()->find($request->geofence_id)))
            throw new ResourseNotFoundException('front.geofence');

        $positions = $device->positions()
            ->where('time', '>', $request->from)
            ->where('time', '<', $request->to)
            ->get();

        if (is_null($positions))
            throw new ResourseNotFoundException('front.position');

        $min_away_by = INF;
        $closest_point = null;
        
        foreach ($positions as $position) {
            $point = [
                'latitude'  => $position->latitude,
                'longitude' => $position->longitude,
            ];

            $away_by = $geofence->pointAwayBy($point);

            if ($away_by == 0)
                return response()->json(['status' => 1, 'was_in' => true]);

            if ($away_by < $min_away_by) {
                $closest_point = $point;
                $min_away_by = $away_by;
            }
        }

        return response()->json([
            'status'        => 1,
            'was_in'        => false,
            'closest_point' => $closest_point,
        ]);
    }

    public function stayInGeofence(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from'        => 'required|date',
            'to'          => 'required|date',
            'device_id'   => 'required|integer',
            'geofence_id' => 'required|integer',
        ]);

        if ($validator->fails())
            return response()->json(['status' => 0, 'errors' => $validator->errors()]);

        $device = Device::find($request->device_id);

        $this->checkException('devices', 'own', $device);

        $geofence = Geofence::find($request->geofence_id);

        $this->checkException('geofences', 'own', $geofence);

        $geofences = [$geofence];
        $history = new DeviceHistory($device);
        $history->setGeofences($geofences);
        $history->setRange($request->from, $request->to);
        $history->registerActions([
            Duration::class,
            GroupGeofenceInOut::class,
        ]);

        $result = $history->get();
        $total  = $result['groups']->merge();

        try {
            $duration = $total->stats()->human('duration');
            $seconds  = $total->stats()->get('duration')->get();
        } catch (\Exception $e) {
            $duration = null;
            $seconds  = 0;
        }

        return response()->json([
            'status'   => 1,
            'duration' => $duration,
            'seconds'  => $seconds,
        ]);

    }

    public function uploadImage($device_id)
    {
        $device = DeviceRepo::find($device_id);

        $this->checkException('devices', 'update', $device);
        DeviceImageValidator::validate('upload', $this->data);

        (new DeviceImageService($device))->save($this->data['image']);

        return ['status' => 1];
    }

    public function deleteImage($device_id)
    {
        $device = DeviceRepo::find($device_id);
        $this->checkException('devices', 'update', $device);

        (new DeviceImageService($device))->delete();

        return ['status' => 1];
    }
}
