<?php namespace ModalHelpers;

use App\Exceptions\DeviceLimitException;
use App\Exceptions\PermissionException;
use App\Transformers\ApiV1\DeviceFullJsonTransformer;
use App\Transformers\ApiV1\DeviceFullTransformer;
use App\Transformers\Device\DeviceMapFullTransformer;
use App\Transformers\Device\DeviceMapTransformer;
use App\Transformers\Event\EventLatestTransformer;
use Carbon\Carbon;
use CustomFacades\ModalHelpers\SensorModalHelper;
use CustomFacades\ModalHelpers\ServiceModalHelper;
use CustomFacades\Repositories\DeviceGroupRepo;
use CustomFacades\Repositories\DeviceIconRepo;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\DeviceSensorRepo;
use CustomFacades\Repositories\SensorGroupRepo;
use CustomFacades\Repositories\TimezoneRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Repositories\DeviceCameraRepo;
use CustomFacades\Validators\DeviceConfiguratorFormValidator;
use CustomFacades\Validators\DeviceFormValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\ApnConfig;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceConfig;
use Tobuli\Entities\DeviceModel;
use Tobuli\Entities\DeviceType;
use Tobuli\Entities\Event;
use Tobuli\Entities\User;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\SMS\SMSGatewayManager;
use Tobuli\Services\AlertSoundService;
use Tobuli\Services\CustomValuesService;
use Tobuli\Services\DeviceConfigService;
use Formatter;
use Tobuli\Services\DeviceSensorsService;
use Tobuli\Services\DeviceService;
use Tobuli\Services\DeviceUsersService;
use Tobuli\Services\EntityLoader\UsersLoader;
use Tobuli\Services\FractalSerializers\WithoutDataArraySerializer;
use Tobuli\Services\FractalTransformerService;

class DeviceModalHelper extends ModalHelper
{
    private $device_fuel_measurements = [];
    private $configService;
    private $customValueService;
    private $deviceService;

    /**
     * @var DeviceUsersService
     */
    private $deviceUsersService;

    /**
     * @var FractalTransformerService
     */
    private $transformerService;

    /**
     * @var UsersLoader
     */
    private $usersLoader;

    public function __construct(
        DeviceConfigService $configService,
        DeviceService $deviceService,
        CustomValuesService $customValueService,
        FractalTransformerService $transformerService
    ) {
        parent::__construct();

        $this->device_fuel_measurements = [
            [
                'id' => 1,
                'title' => trans('front.l_km'),
                'fuel_title' => trans('front.liters'),
                'distance_title' => '100 ' . strtolower(trans('front.kilometers')),
                'cost_title' => strtolower(trans('front.liter')),
            ],
            [
                'id' => 2,
                'title' => trans('front.mpg'),
                'fuel_title' => trans('front.miles'),
                'distance_title' => strtolower(trans('front.gallon')),
                'cost_title' => strtolower(trans('front.gallon')),
            ],
            [
                'id' => 3,
                'title' => trans('front.kwh_km'),
                'fuel_title' => trans('front.kwhs'),
                'distance_title' => strtolower(trans('front.kilometer')),
                'cost_title' => strtolower(trans(trans('front.kwh'))),
            ],
            [
                'id' => 4,
                'title' => trans('front.l_h'),
                'fuel_title' => trans('front.liters'),
                'distance_title' => strtolower(trans('front.hour')),
                'cost_title' => strtolower(trans('front.liter')),
            ],
            [
                'id' => 5,
                'title' => trans('front.km_l'),
                'fuel_title' => trans('front.kilometers'),
                'distance_title' => strtolower(trans('front.liter')),
                'cost_title' => strtolower(trans('front.liter')),
            ],
        ];

        $this->icons_types = [
            'arrow' => trans('front.arrow'),
            'rotating' => trans('front.rotating_icon'),
            'icon' => trans('front.icon')
        ];

        $this->device_icon_colors = [
            'green'  => trans('front.green'),
            'yellow' => trans('front.yellow'),
            'red'    => trans('front.red'),
            'blue'   => trans('front.blue'),
            'orange' => trans('front.orange'),
            'black'  => trans('front.black'),
        ];

        $this->fuel_detect_stop_durations = [
            60 => '1 ' . trans('front.minute_short'),
            120 => '2 ' . trans('front.minute_short'),
            180 => '3 ' . trans('front.minute_short'),
            300 => '5 ' . trans('front.minute_short'),
        ];

        $this->expiration_date_select = [
            '0000-00-00 00:00:00' => trans('front.unlimited'),
            '1' => trans('validation.attributes.expiration_date')
        ];

        $this->configService = $configService;
        $this->deviceService = $deviceService;
        $this->customValueService = $customValueService;
        $this->transformerService = $transformerService->setSerializer(WithoutDataArraySerializer::class);
        $this->deviceUsersService = new DeviceUsersService();

        $this->usersLoader = new UsersLoader($this->user);
        $this->usersLoader->setRequestKey('user_id');
    }

    public function createData() {
        $perm = request()->get('perm');

        if ($perm == null || ($perm != null && $perm != 1)) {
            if ($perm != null && $perm != 2) {
                if ($this->deviceUsersService->isLimitReached($this->user)) {
                    throw new DeviceLimitException();
                }
            }

            $this->checkException('devices', 'create');
        }

        $icons_type = $this->icons_types;
        $device_icon_colors = $this->device_icon_colors;
        $fuel_detect_sec_after_stop_options = $this->fuel_detect_stop_durations;
        $expiration_date_select = $this->expiration_date_select;
        $device_fuel_measurements = $this->device_fuel_measurements;
        $device_fuel_measurements_select = Arr::pluck($this->device_fuel_measurements, 'title', 'id');

        $device_icons = DeviceIconRepo::getMyIcons($this->user->id);
        $device_icons_grouped = $device_icons
            ->filter(function($icon) { return $icon->type != 'arrow'; })
            ->groupBy('type');

        $users = [];

        $device_groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $timezones = TimezoneRepo::order()
            ->pluck('title', 'id')
            ->prepend(trans('front.default'), '0')
            ->map(function($timezone) { return str_replace('UTC ', '', $timezone); })
            ->all();

        $sensor_groups = isAdmin()
            ? SensorGroupRepo::getWhere([], 'title')
                ->pluck('title', 'id')
                ->prepend(trans('front.none'), '0')
                ->all()
            : [];

        $device_types = DeviceType::active()->get()->pluck('title', 'id')->prepend(trans('front.none'), '');
        $models = $this->getDeviceModels(new Device());

        if ($this->api) {
            $timezones = apiArray($timezones);
            $device_groups = apiArray($device_groups);
            $sensor_groups = apiArray($sensor_groups);
            $models = apiArray($models);
            $users = UserRepo::getUsers($this->user)->toArray();
        }

        $device_configs = [];
        $apn_configs = [];

        if ($this->user->able('configure_device')) {
            $device_configs = DeviceConfig::active()
                ->get()
                ->pluck('fullName', 'id');
            $apn_configs = ApnConfig::active()
                ->get()
                ->pluck('name', 'id');
        }

        return compact('device_groups', 'sensor_groups',
            'device_fuel_measurements', 'device_icons', 'users', 'timezones',
            'expiration_date_select', 'device_fuel_measurements_select',
            'icons_type', 'device_icons_grouped', 'device_icon_colors',
            'device_configs', 'apn_configs', 'device_types', 'fuel_detect_sec_after_stop_options',
            'models'
        );
    }

    public function create()
    {
        $this->checkException('devices', 'store');

        if ($this->deviceUsersService->isLimitReached($this->user)) {
            throw new DeviceLimitException();
        }

        $this->data['imei'] = isset($this->data['imei']) ? trim($this->data['imei']) : null;

        if (array_key_exists('enable_expiration_date', $this->data) && empty($this->data['enable_expiration_date'])) {
            $this->data['expiration_date'] = null;
        }

        if (!empty($this->data['expiration_date'])) {
            $this->data['expiration_date'] = Formatter::time()->reverse($this->data['expiration_date']);
        }

        $this->data['icon_colors'] = $this->setIconColors();

        $this->data = onlyEditables(new Device(), $this->user, $this->data);

        $this->setAbleUsers();
        $this->usersReachedLimit();

        DeviceFormValidator::validate('create', $this->data);

        $device = $this->deviceService->create($this->data);

        if ($this->data['configure_device'] ?? false) {
            $this->configureDevice($device);
        }

        return ['status' => 1, 'id' => $device->id,];
    }

    public function editData() {
        $device_id = $this->data['id']
            ?? request()->route('id')
            ?? $this->data['device_id']
            ?? null;

        $item = Device::find($device_id);

        $this->checkException($item && $item->isBeacon() ? 'beacons' : 'devices', 'edit', $item);

        $timezone_id = $item->timezone_id;
        $group_id = $this->user->devices()->find($item->id)->pivot->group_id ?? null;

        $icons_type = $this->icons_types;
        $device_icon_colors = $this->device_icon_colors;
        $fuel_detect_sec_after_stop_options = $this->fuel_detect_stop_durations;
        $expiration_date_select = $this->expiration_date_select;
        $device_fuel_measurements = $this->device_fuel_measurements;
        $device_fuel_measurements_select = Arr::pluck($this->device_fuel_measurements, 'title', 'id');

        $device_icons = DeviceIconRepo::getMyIcons($this->user->id);
        
        $device_icons_grouped = [];

        foreach ($device_icons as $dicon) {
            if ($dicon['type'] == 'arrow') {
                continue;
            }

            if (!array_key_exists($dicon['type'], $device_icons_grouped)) {
                $device_icons_grouped[$dicon['type']] = [];
            }

            $device_icons_grouped[$dicon['type']][] = $dicon;
        }

        $device_groups = DeviceGroupRepo::getWhere(['user_id' => $this->user->id])
            ->pluck('title', 'id')
            ->prepend(trans('front.ungrouped'), '0')
            ->all();

        $sensors = SensorModalHelper::paginated($item->id);
        $services = ServiceModalHelper::paginated($item->id);

        $detects = $this->getDetectSensors($item);
        $engine_hours = $detects['engine_hours'];
        $detect_engine = $detects['engine'];
        $detect_distance = $detects['distance'];
        $detect_speed = $detects['speed'];

        $timezones = TimezoneRepo::order()
            ->pluck('title', 'id')
            ->prepend(trans('front.default'), '0')
            ->map(function($timezone) { return str_replace('UTC ', '', $timezone); })
            ->all();

        $sensor_groups = isAdmin()
            ? SensorGroupRepo::getWhere([], 'title')
                ->pluck('title', 'id')
                ->prepend(trans('front.none'), '0')
                ->all()
            : [];

        $models = $this->getDeviceModels($item);

        $users = [];
        $sel_users = [];
        if ($this->api) {
            $device_groups = apiArray($device_groups);
            $timezones = apiArray($timezones);
            $models = apiArray($models);
            $users = UserRepo::getUsers($this->user)->toArray();
            $sel_users = $item->users->pluck('id', 'id')->all();
        }

        $device_cameras = DeviceCameraRepo::searchAndPaginate(['filter' => ['device_id' => $device_id]], 'id', 'desc', 10);

        $device_types = DeviceType::active()->get()->pluck('title', 'id')->prepend(trans('front.none'), '');

        return compact('device_id', 'engine_hours', 'detect_engine', 'detect_distance', 'detect_speed',
            'device_groups', 'sensor_groups', 'item',
            'device_fuel_measurements', 'device_icons', 'sensors', 'services',
            'expiration_date_select', 'timezones',
            'users', 'sel_users', 'group_id', 'timezone_id',
            'device_fuel_measurements_select', 'icons_type',
            'device_icons_grouped', 'device_icon_colors', 'device_cameras', 'device_types',
            'fuel_detect_sec_after_stop_options', 'models'
        );
    }

    public function edit()
    {
        $this->data['id'] = $this->data['id']
            ?? $this->data['device_id']
            ?? null;

        $item = Device::find($this->data['id']);

        $this->checkException('devices', 'update', $item);

        if ($item->isBeacon())
            throw new ValidationException(['id' => 'Device is kind of beacon.']);

        if ( ! empty($this->data['timezone_id']) && $this->data['timezone_id'] != 57 && $item->isCorrectUTC()) {
            throw new ValidationException(['timezone_id' => 'Device time is correct. Check your timezone Setup -> Main -> Timezone']);
        }

        if (array_key_exists('enable_expiration_date', $this->data) && empty($this->data['enable_expiration_date'])) {
            $this->data['expiration_date'] = null;
        }

        if (!empty($this->data['expiration_date'])) {
            $this->data['expiration_date'] = Formatter::time()->reverse($this->data['expiration_date']);
        }

        $this->data['icon_colors'] = $this->setIconColors($item);

        $this->data = onlyEditables($item, $this->user, $this->data);

        $this->setAbleUsers($item);

        $this->usersReachedLimit($item);

        DeviceFormValidator::validate('update', $this->data, $item->id);

        $this->deviceService->update($item, $this->data);

        return ['status' => 1, 'id' => $item->id];
    }

    public function resetAppUuid(int $id): array
    {
        /** @var Device $item */
        $item = Device::findOrFail($id);

        $this->checkException('devices', 'edit', $item);

        $item->app_uuid = null;
        $success = $item->save();

        return ['status' => (int)$success, 'id' => $item->id];
    }

    public function destroy()
    {
        $imei = $this->data['imei'] ?? null;

        if (!is_null($imei)) {
            $item = DeviceRepo::whereImei($imei);
        } else {
            $device_id = $this->data['id'] ?? $this->data['device_id'] ?? null;
            $item = DeviceRepo::find($device_id);
        }

        $this->checkException('devices', 'remove', $item);

        $this->deviceService->delete($item);

        return ['status' => 1, 'id' => $item->id, 'deleted' => 1];
    }

    public function detach()
    {
        $device_id = $this->data['id']
            ?? $this->data['device_id']
            ?? null;

        $item = DeviceRepo::find($device_id);

        $this->checkException('devices', 'own', $item);

        $item->users()->detach($this->user->id);

        return ['status' => 1];
    }

    public function changeActive()
    {
        $validator = Validator::make($this->data, [
            'id' => 'required_without:group_id',
            'group_id' => 'required_without:id',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $active = (isset($this->data['active']) && filter_var($this->data['active'], FILTER_VALIDATE_BOOLEAN)) ? 1 : 0;

        if (array_key_exists('group_id', $this->data)) {
            $updateCount = $this->deviceUsersService->setVisibleGroups($this->user, $this->data['group_id'], $active);
        } else {
            $updateCount = $this->deviceUsersService->setVisibleDevices($this->user, $this->data['id'], $active);
        }

        return ['status' => $updateCount ? 1 : 0];
    }

    public function itemsJson()
    {
        $this->checkException('devices', 'view');

        $now  = time();
        $time = empty($this->data['time']) ? $now - 5 : intval($this->data['time']);

        $query = empty($this->data['id']) ? $this->user->devices() : $this->user->accessibleDevices();

        $items = $query
            ->filter($this->data)
            ->updatedAfter(date('Y-m-d H:i:s', $time))
            ->clearOrdersBy()
            ->get();

        $transformer = $this->api ? DeviceFullJsonTransformer::class : DeviceMapTransformer::class;

        $items = $this->transformerService->collection($items, $transformer)->toArray();

        if ($this->user->perm('events', 'view')) {
            $eventTime = ($now - $time > 300) ? $now - 300 : $time;

            $events = Event::userAccessible($this->user)->higherTime($eventTime, $this->data['id'] ?? null)->get();
            $events = $events->filter(fn ($event) => Arr::get($event, 'alert.notifications.popup.active', true));
            $events = $this->transformerService->collection($events, EventLatestTransformer::class)->toArray();
        } else {
            $events = [];
        }

        return [
            'items' => $items,
            'events' => $events,
            'time' => $now,
            'version' => Config::get('tobuli.version')
        ];
    }

    private function getDeviceModels(Device $item): array
    {
        if (!$this->user->can('view', $item, 'model_id')) {
            return [];
        }

        return DeviceModel::where(
            fn (Builder $query) => $query->where('active', 1)->orWhere('id', $item->model_id)
        )
            ->pluck('title', 'id')
            ->prepend(trans('front.none'), '')
            ->all();
    }

    private function usersReachedLimit($device = null)
    {
        if (empty($this->data['user_id'])) {
            return;
        }

        $users = $this->deviceUsersService->getUsersReachedLimit($this->data['user_id'], $device);

        if (!$users->isEmpty()) {
            throw new ValidationException(['user_id' => trans('validation.attributes.devices_limit') . ': ' . $users->implode('email', ', ')]);
        }
    }

    private function configureDevice(Device $device)
    {
        if (! $this->user->able('configure_device')) {
            throw new PermissionException(['id' => trans('front.dont_have_permission')]);
        }

        DeviceConfiguratorFormValidator::validate('configure', $this->data);

        $config = DeviceConfig::find($this->data['config_id']);

        $smsManager = new SMSGatewayManager();
        $gatewayArgs = settings('sms_gateway.use_as_system_gateway')
            ? ['request_method' => 'system']
            : null;

        $smsSenderService = $smsManager->loadSender($this->user, $gatewayArgs);
        $apnData = request()->all(['apn_name', 'apn_username', 'apn_password']);

        if ($this
            ->configService
            ->setSmsManager($smsSenderService)
            ->configureDevice($device->sim_number, $apnData, $config->commands)
        ) {
            return ['status' => 2];
        }

        throw new \Exception(trans('validation.cant_configure_device'));
    }

    private function setAbleUsers($device = null)
    {
        if (!$this->api) {
            unset($this->data['user_id']);
        }

        if (!(isAdmin() && $this->user->can('edit', new User()))){
            unset($this->data['user_id']);
        }

        if (is_null($device) && empty($this->data['user_id']) ) {
            $this->data['user_id'] = [$this->user->id];
        }

        if ($this->usersLoader->hasSelect()) {
            if ($device)
                $this->usersLoader->setQueryStored($device->users());
            $this->data['user_id'] = $this->usersLoader;
        } else {
            if ($users = $this->data['user_id'] ?? []) {
                $this->data['user_id'] = User::userAccessible($this->user)->whereIn('id', $users)->pluck('id')->all();
            }
        }
    }

    protected function setIconColors($item = null)
    {
        $icon_colors = $item
            ? $item->icon_colors + settings('device.status_colors.colors')
            : settings('device.status_colors.colors');

        foreach ($icon_colors as $status => $color) {
            $key = "icon_$status";

            if (array_key_exists($key, $this->data) && array_key_exists($this->data[$key], $this->device_icon_colors)) {
                $icon_colors[$status] = $this->data[$key];
            }
        }

        return $icon_colors;
    }

    protected function getDetectSensors($device)
    {
        $engine_hours = [
            'gps' => trans('front.gps')
        ];

        $engine = [
            'gps' => trans('front.gps')
        ];

        $speed = [
            'gps' => trans('front.gps')
        ];

        $distance = [
            'gps' => trans('front.gps')
        ];

        $sensors = $device->sensors()
            ->whereIn('type', ['acc', 'engine', 'ignition', 'engine_hours', 'odometer', 'speed_ecm'])
            ->get();

        foreach ($sensors as $sensor) {

            $title = trans('front.sensor') . ': ' . $sensor->type_title;

            switch ($sensor->type) {
                case 'acc':
                case 'engine':
                case 'ignition':
                    $engine_hours[$sensor->type] = $title;
                    $engine[$sensor->type] = $title;
                    break;
                case 'engine_hours':
                    $engine_hours[$sensor->type] = $title;
                    break;
                case 'odometer':
                    $distance[$sensor->type] = $title;
                    break;
                case 'speed_ecm':
                    $speed[$sensor->type] = $title;
                    break;
            }
        }

        return [
            'engine_hours' => $engine_hours,
            'engine'       => $engine,
            'distance'     => $distance,
            'speed'        => $speed,
        ];
    }


    /**
     * Global Robocall master switch (robo_call_settings.enabled).
     * Fails closed: a missing row or any DB error means OFF.
     */
    private function roboCallGloballyEnabled()
    {
        try {
            return (int) DB::table('hypegps_web.robo_call_settings')->where('id', 1)->value('enabled') === 1;
        } catch (\Throwable $e) {
            \Log::error('Robo-call global switch unreadable, treating as OFF: ' . $e->getMessage());
            return false;
        }
    }

    public function checkAndTriggerRoboCall($events, $fallback = null)
    {
        if (!$this->roboCallGloballyEnabled()) {
            return false;
        }

        foreach ($events as $event) {
            $event = is_object($event) ? (array) $event : $event;
            $roboConfig = $event['alert']['notifications']['robo_call'] ?? null;

            if (!is_array($roboConfig) || empty($roboConfig['active'])) {
                continue;
            }

            $eventId = (int) ($event['id'] ?? 0);
            $deviceId = (int) ($event['device_id'] ?? 0);
            $alertId = (int) ($event['alert_id'] ?? 0);

            if (!$eventId || !$deviceId || !$alertId) {
                continue;
            }

            $voiceId = isset($roboConfig['input']) ? (int) $roboConfig['input'] : null;
            if (!$voiceId) continue;

            $text2 = $roboConfig['text2'] ?? null;
            $text3 = $roboConfig['text3'] ?? null;
            $text4 = $roboConfig['text4'] ?? null;
            $text5 = $roboConfig['text5'] ?? null;

            $newtext2 = $text2;
            if ($voiceId == 35) {
                $newtext2 = null;
            } elseif ($voiceId == 34) {
                $newtext2 = Carbon::parse($event['created_at'])->format('M d Y');
            } elseif ($voiceId == 32) {
                $newtext2 = $event['additional']['geofence'] ?? 'Exit Unknown';
            }

            $userIds = DB::table('hypegps_web.user_device_pivot')->where('device_id', $deviceId)->get()->pluck('user_id')->values()->toArray();
            if (empty($userIds)) continue;

            $users = DB::table('hypegps_web.users')->where('is_call_enabled', 1)->whereIn('id', $userIds)->get();
            $dialedNumbers = [];

            foreach ($users as $user) {
                // Fallback pass: only the one user the fallback was scheduled for.
                if ($fallback && (int) $user->id !== (int) $fallback['user_id']) continue;

                $numbers = \App\Services\Robocall\RobocallNumbers::forUser($user);
                $callerId = $fallback ? $fallback['number'] : ($numbers[0] ?? null);

                if (empty($callerId)) {
                    \Log::warning('Robo-call skipped: no valid phone number.', ['event_id' => $eventId, 'user_id' => $user->id]);
                    continue;
                }

                // The fallback pass may only dial the user's configured secondary number.
                if ($fallback && !in_array($callerId, array_slice($numbers, 1), true)) {
                    continue;
                }

                if (isset($dialedNumbers[$callerId])) {
                    \Log::info('Robo-call skipped: duplicate phone in same event.', ['event_id' => $eventId, 'caller_id' => $callerId]);
                    continue;
                }

                $lockKey = 'rc_' . md5($eventId . '|' . $callerId);
                $lockResult = DB::select('SELECT GET_LOCK(?, 3) AS acquired', [$lockKey]);
                $lockAcquired = !empty($lockResult) && isset($lockResult[0]->acquired) && (int) $lockResult[0]->acquired === 1;

                if (!$lockAcquired) {
                    \Log::info('Robo-call skipped: event lock already owned.', ['event_id' => $eventId, 'caller_id' => $callerId]);
                    continue;
                }

                try {
                    $alreadySent = DB::table('hypegps_web.robo_calls')->where('event_id', $eventId)->where('call_to', $callerId)->where('is_call_send', 1)->exists();
                    if ($alreadySent) {
                        \Log::info('Robo-call skipped: exact event already sent.', ['event_id' => $eventId, 'caller_id' => $callerId]);
                        continue;
                    }

                    $cooldownCfg = \App\Services\Robocall\RobocallSettings::get(); $cooldownAt = Carbon::now()->subHours(($voiceId == 32) ? (int) $cooldownCfg['geofence_cooldown_hours'] : (int) $cooldownCfg['cooldown_hours']);
                    $recentSameAlert = DB::table('hypegps_web.robo_calls')->where('device_id', $deviceId)->where('alert_id', $alertId)->where('call_to', $callerId)->where('is_call_send', 1)->where('created_at', '>=', $cooldownAt)->exists();

                    if ($recentSameAlert) {
                        \Log::info('Robo-call skipped: alert cooldown active.', ['event_id' => $eventId, 'device_id' => $deviceId, 'alert_id' => $alertId, 'caller_id' => $callerId]);
                        continue;
                    }

                    $dialedNumbers[$callerId] = true;
                    $alert = DB::table('hypegps_web.alerts')->where('id', $alertId)->first();
                    if (!$alert) continue;

                    $api = DB::table('hypegps_web.robo_call_api_url')->find(1);
                    if (!$api || empty($api->link)) {
                        \Log::error('Robo-call API URL not configured.');
                        continue;
                    }

                    $apiKey = \App\Services\Robocall\RobocallSettings::apiKey();
                    if (empty($apiKey)) {
                        \Log::error('Robo-call API key is missing from VOICE_API_KEY.', ['event_id' => $eventId, 'caller_id' => $callerId]);
                        continue;
                    }

                    $queryParams = [
                        'api_key'   => $apiKey, 'caller_id' => $callerId, 'amount'    => (int) \App\Services\Robocall\RobocallSettings::get()['amount'],
                        'voice_id'  => $voiceId, 'key1'      => $alert->id, 'key2'      => $alert->id,
                        'text1'     => $event['device_name'] ?? '', 'text2'     => $newtext2,
                        'text3'     => $text3, 'text4'     => $text4, 'text5'     => $text5,
                    ];

                    $client = new \GuzzleHttp\Client(['connect_timeout' => 10, 'timeout' => 25, 'http_errors' => false]);
                    \Log::info('Robo-call API request started.', ['event_id' => $eventId, 'user_id' => $user->id, 'caller_id' => $callerId, 'voice_id' => $voiceId]);
                    
                    $response = $client->get($api->link, ['query' => $queryParams]);
                    $responseBody = $response->getBody()->getContents();
                    $decoded = json_decode($responseBody, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->insertRoboCall($user, $event, $alert, 'failed', $responseBody, $callerId, (string) $response->getStatusCode(), 'Invalid JSON response', null, $voiceId, 0, null, $responseBody);
                        continue;
                    }

                    $responseStatus = $decoded['status'] ?? $response->getStatusCode();
                    $message = $decoded['message'] ?? null;
                    $robocallId = $decoded['data']['call_id'] ?? null;
                    $responseVoiceId = $decoded['data']['voice_id'] ?? $voiceId;
                    $accepted = in_array($response->getStatusCode(), [200, 201], true) && !empty($decoded['data']);

                    $this->insertRoboCall($user, $event, $alert, $accepted ? 'ringing' : 'failed', $responseBody, $callerId, (string) $responseStatus, $message, null, $responseVoiceId, $accepted ? 1 : 0, $robocallId, json_encode($decoded));
                    \Log::info('Robo-call API request completed.', ['event_id' => $eventId, 'user_id' => $user->id, 'caller_id' => $callerId, 'accepted' => $accepted, 'call_id' => $robocallId]);

                } catch (\Exception $e) {
                    \Log::error('Robo-call exception.', ['event_id' => $eventId, 'caller_id' => $callerId, 'message' => $e->getMessage()]);
                } finally {
                    DB::select('SELECT RELEASE_LOCK(?)', [$lockKey]);
                }
            }
        }
        return false;
    }

    public function feedbackCalling()
    {
        $feedbacks = DB::table('hypegps_web.robo_call_queue')
            ->leftJoin('hypegps_web.alerts', 'hypegps_web.robo_call_queue.alert_id', '=', 'hypegps_web.alerts.id')
            ->where('hypegps_web.robo_call_queue.status', 'pending')
            ->select(['hypegps_web.robo_call_queue.*', 'hypegps_web.alerts.user_id', 'hypegps_web.alerts.notifications', 'hypegps_web.alerts.created_at as alert_created_at'])->get();

        $currentDate = Carbon::now()->toDateString();
        $currentTime = Carbon::now()->format('H:i');

        foreach ($feedbacks as $feedback) {
            $schedule = json_decode($feedback->schedule, true);
            if (is_string($schedule)) { $schedule = json_decode($schedule, true); }
            if (!is_array($schedule)) { \Log::error("Invalid JSON: " . $feedback->schedule); continue; }
            
            $createdAt = Carbon::parse($feedback->created_at);
            foreach ($schedule as $day => $timeSlots) {
                $scheduleDate = Carbon::parse($createdAt)->modify("next $day")->toDateString();
                $startTime    = reset($timeSlots);
                $endTime      = end($timeSlots);

                if ($scheduleDate == $currentDate && $currentTime >= $startTime && $currentTime <= $endTime) {
                    \Log::info("API calling started for Feedback ID: " . $feedback->id . " call on " . $feedback->phone_number);
                    $this->callApiFeedback($feedback);
                    DB::table('hypegps_web.robo_call_queue')->where('id', $feedback->id)->update(['status' => 'processing']);
                }
            }
        }
    }

    public function callApiFeedback($feedback)
    {
        \Log::info("feedback inserting in Database.", (array) $feedback);
        $apiKey   = \App\Services\Robocall\RobocallSettings::apiKey();
        $callerId = $this->formatPhoneNumber($feedback->phone_number);
        $api      = DB::table('hypegps_web.robo_call_api_url')->find(1);

        if (!$api) { \Log::error("API URL Not Found in Database"); return false; }

        $queryParams = ['api_key' => $apiKey, 'caller_id' => $callerId, 'amount' => (int) \App\Services\Robocall\RobocallSettings::get()['amount'], 'voice_id' => 35, 'text1' => "Thank You"];
        $client = new \GuzzleHttp\Client();

        try {
            $response     = $client->get($api->link . '?' . http_build_query($queryParams));
            $responseBody = $response->getBody()->getContents();

            if (in_array($response->getStatusCode(), [200, 201])) {
                $callId = json_decode($responseBody, true)['data']['call_id'];
                $this->insertRoboCallFeedback($feedback->user_id, $feedback->device_customer_name, $feedback->alert_id, 'ringing', $responseBody, $callId);
            } else {
                $responseData = json_decode($responseBody, true);
                $this->insertRoboCallFeedback($feedback->user_id, $feedback->device_customer_name, $feedback->alert_id, 'failed', $responseBody, null, $responseData['error_code'] ?? 'Unknown', $responseData['error_message'] ?? 'No message');
            }
        } catch (\Exception $e) {
            $this->insertRoboCallFeedback($feedback->user_id, $feedback->device_customer_name, $feedback->alert_id, 'failed', 'No Response Body', null, null, $e->getMessage());
        }
    }

    private function formatPhoneNumber($phoneNumber, $index = 0)
    {
        if (empty($phoneNumber)) return 'phone number not found';
        $cleanedPhoneNumber = str_replace(['-', ' '], '', $phoneNumber);
        $cleanedPhoneNumber = str_replace(['/', '_', '=', ','], '|', $cleanedPhoneNumber);
        $numbers     = explode('|', $cleanedPhoneNumber);
        $phoneNumber = trim($numbers[$index] ?? $numbers[0] ?? $cleanedPhoneNumber);
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        if (preg_match('/^92(\d{10})$/', $phoneNumber, $matches)) { return '0' . $matches[1]; } 
        elseif (preg_match('/^\+92(\d{10})$/', $phoneNumber, $matches)) { return '0' . $matches[1]; } 
        elseif (preg_match('/^3(\d{9})$/', $phoneNumber, $matches)) { return '0' . $phoneNumber; } 
        elseif (preg_match('/^03\d{9}$/', $phoneNumber)) { return $phoneNumber; }
        return "$phoneNumber wrong number";
    }




private function insertRoboCallFeedback($user, $event, $alert, $status, $responseBody, $callId = null, $errorCode = null, $errorMessage = null)
    {
        DB::table('hypegps_web.robo_calls')->insertGetId([
            'user_id' => $user, 'alert_id' => $alert, 'alert_name' => "FeedBack Call", 'call_to' => $callId,
            'status' => $status, 'device_name' => $event, 'is_call_send' => ($status === 'ringing') ? 1 : 0,
            'rc_id' => $callId, 'rc_voice_id' => 35, 'error_code' => $errorCode, 'error_message' => $errorMessage,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function insertRoboCall($user, $event, $alert, $status, $responseBody, $callerId = null, $errorCode = null, $errorMessage = null, $timeAgo = null, $voiceId = null, $is_call_send = 0, $robocallId = null, $decoded = null)
    {
        $signature = md5(($event['device_id'] ?? '') . ($event['alert_id'] ?? '') . ($event['name'] ?? 'Alert' ?? ''));
        try {
            DB::transaction(function () use ($user, $event, $alert, $status, $callerId, $errorCode, $errorMessage, $timeAgo, $voiceId, $is_call_send, $robocallId, $decoded, $signature) {
                DB::table('hypegps_web.robo_calls')->insert([
                    'alert_signature'  => $signature, 'user_id'          => $user->id,
                    'device_id'        => $event['device_id'], 'event_id'         => $event['id'],
                    'device_name'      => $event['device_name'], 'alert_id'         => $alert->id,
                    'alert_name'       => $alert->name, 'type'             => $event['type'] ?? 'unknown',
                    'time'             => $event['time'] ?? now(), 'call_to'          => $callerId,
                    'status'           => $status, 'name'             => $event['name'] ?? 'Alert',
                    'detail'           => $event['detail'] ?? '', 'is_call_send'     => $is_call_send,
                    'rc_id'            => $robocallId, 'rc_voice_id'      => $voiceId,
                    'error_code'       => $errorCode, 'error_message'    => $errorMessage,
                    'decoded_response' => $decoded, 'retry_time'       => $timeAgo,
                    'created_at'       => now(), 'updated_at' => now(),
                ]);
            });
        } catch (\Exception $e) {
            echo "
❌ DB INSERT FAILED: " . $e->getMessage() . "
"; \Log::error("DB Transaction failed: " . $e->getMessage());
        }
    }
}