<?php

namespace Tobuli\Entities;

use App\Events\Device\DeviceDisabled;
use App\Events\Device\DeviceEnabled;
use App\Events\Device\DeviceSubscriptionActivate;
use App\Events\Device\DeviceSubscriptionRenew;
use App\Jobs\TrackerConfigWithRestart;
use Carbon\Carbon;
use Formatter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Sensors\Types\Blocked;
use Tobuli\Services\DeviceImageService;
use Tobuli\Services\DeviceModelCache;
use Tobuli\Services\EventWriteService;
use Tobuli\Traits\AttributesRelationsGetter;
use Tobuli\Traits\ChangeLogs;
use Tobuli\Traits\Chattable;
use Tobuli\Traits\Customizable;
use Tobuli\Traits\DisplayTrait;
use Tobuli\Traits\EventLoggable;
use Tobuli\Traits\FcmTokensTrait;
use Tobuli\Traits\Filterable;
use Tobuli\Traits\Includable;
use Tobuli\Traits\Nameable;
use Tobuli\Traits\Searchable;
use Tobuli\Utils\NetworkUtil;

class Device extends AbstractEntity implements DisplayInterface, FcmTokenableInterface, ChattableInterface
{
    use Chattable, EventLoggable, Searchable, Filterable, Includable, Nameable, Customizable, FcmTokensTrait, AttributesRelationsGetter, HasFactory,
        ChangeLogs, DisplayTrait;

    const STATUS_ACK     = 'ack';
    const STATUS_OFFLINE = 'offline';
    const STATUS_ONLINE  = 'online';
    const STATUS_ENGINE  = 'engine';
    const STATUS_BLOCKED = 'blocked';

    const KIND_GENERAL = 0;
    const KIND_BEACON = 1;

    const STOP_DURATION_OFFSET = 3;

    /**
     * tc_devices.lastupdate (ack time) and tc_positions.servertime are written
     * by Traccar independently, so lastupdate is routinely a few seconds later
     * than the position it belongs to. Differences up to this many seconds are
     * NOT treated as "ACK without a new position".
     */
    const ACK_TOLERANCE = 15;

    public static array $displayField = ['imei', 'name'];

    protected $table = 'devices';

    protected $fillable = array(
        'deleted',
        'traccar_device_id',
        'timezone_id',
        'name',
        'imei',
        'icon_id',
        'model_id',
        'fuel_measurement_id',
        'fuel_quantity',
        'fuel_price',
        'fuel_per_km',
        'sim_number',
        'device_model',
        'plate_number',
        'vin',
        'registration_number',
        'object_owner',
        'additional_notes',
        'comment',
        'expiration_date',
        'tail_color',
        'tail_length',
        'engine_hours',
        'detect_engine',
        'detect_distance',
        'detect_speed',
        'min_moving_speed',
        'min_fuel_fillings',
        'min_fuel_thefts',
        'snap_to_road',
        'gprs_templates_only',
        'valid_by_avg_speed',
        'icon_colors',
        'parameters',
        'currents',
        'active',
        'forward',
        'sim_activation_date',
        'sim_expiration_date',
        'installation_date',
        'msisdn',
        'device_type_id',
        'app_tracker_login',
        'fuel_detect_sec_after_stop',
        'authentication'
    );

    protected $appends = [];

    protected $casts = [
        'currents' => 'array',
        'icon_colors' => 'array'
    ];

    protected $searchable = [
        'name',
        'imei',
        'sim_number',
        'vin',
        'plate_number',
        'registration_number',
        'object_owner',
        'device_model',
        'additional_notes'
    ];

    protected $filterables = [
        'id',
        'imei',
        'sim_number',
        'group_id'
    ];

    protected $hidden = ['app_uuid'];

    private $attributesRelations = [
        'users_emails' => ['users'],
        'stop_duration' => ['traccar'],
        'speed' => ['traccar'],
        'status' => ['traccar'],
        'status_color' => ['traccar'],
        'protocol' => ['traccar'],
        'time' => ['traccar'],
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($device) {
            // New Traccar architecture: link to tc_devices by uniqueid (IMEI).
            // Do NOT write database_id (column removed).
            $traccarDevice = TraccarDevice::firstOrCreate(
                ['uniqueid' => $device->imei],
                ['name' => $device->name]
            );

            unset($device->database_id);

            $device->traccar_device_id = $traccarDevice->id;
        });

        static::updated(function ($device) {
            if ($device->isDirty('name') || $device->isDirty('imei')) {
                $device->traccar()->update([
                    'name'     => $device->name,
                    'uniqueid' => $device->imei,
                ]);
            }

            if ($device->isDirty('timezone_id')) {
                $device->applyPositionsTimezone();
            }
        });

        static::saving(function (Device $device) {
            if ($device->isDirty('forward')) {
                $hosts = empty($device->forward['ip']) ? [] : semicol_explode($device->forward['ip']);

                foreach ($hosts as $host) {
                    if (NetworkUtil::isHostSelfReferencing($host)) {
                        throw new ValidationException($host . ' host is referring to server IP');
                    }
                }
            }

            if ($device->isDirty('fuel_measurement_id') || $device->isDirty('fuel_quantity')) {
                if ($device->fuel_measurement_id == 4) {
                    $device->fuel_per_km = 0;
                    $device->fuel_per_h = convertFuelConsumption($device->fuel_measurement_id, $device->fuel_quantity);
                } else {
                    $device->fuel_per_km = convertFuelConsumption($device->fuel_measurement_id, $device->fuel_quantity);
                    $device->fuel_per_h = 0;
                }
            }
        });

        static::saved(function (Device $device) {
            $isImeiDirty = $device->isDirty('imei');

            if ($isImeiDirty) {
                UnregisteredDevice::where('imei', $device->imei)->delete();
            }

            if ($isImeiDirty && !$device->wasRecentlyCreated) {
                DeviceModelCache::setDevice($device, $device->getOriginal('imei'));
            } elseif ($isImeiDirty || $device->isDirty('model_id')) {
                DeviceModelCache::setDevice($device);
            }

            if ($device->isDirty('forward'))
                dispatch((new TrackerConfigWithRestart()));

            if ($device->isDirty('active')) {
                event($device->active ? new DeviceEnabled($device) : new DeviceDisabled($device));
            }
        });

        static::deleted(function ($device) {
            DeviceModelCache::deleteDevice($device);
        });
    }

    public function positions()
    {
        if (!$this->traccar) {
            // Device has no matching tc_devices row — return empty query
            // so chained calls (whereBetween/get/cursor/update) don't crash.
            return TraccarPosition::whereRaw('1 = 0');
        }

        return $this->traccar->positions();
    }

    public function positionTraccar()
    {
        if (!$this->traccar) {
            return null;
        }

        $position = $this->traccar->latestPosition;

        if (!$position) {
            return null;
        }

        return $position;
    }

    /**
     * Legacy method. In new Traccar architecture, tc_positions is managed by Traccar.
     * We never create per-device position tables.
     */
    public function createPositionsTable()
    {
        throw new \RuntimeException(
            'createPositionsTable() is not supported in the new Traccar architecture. ' .
            'Traccar manages the tc_positions table centrally.'
        );
    }

    public function icon()
    {
        return $this->hasOne('Tobuli\Entities\DeviceIcon', 'id', 'icon_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function getIconAttribute()
    {
        $icon = $this->getRelationValue('icon');

        return $icon ? $icon->setStatus($this->getStatus()) : null;
    }

    public function traccar()
    {
        return $this->hasOne('Tobuli\Entities\TraccarDevice', 'id', 'traccar_device_id');
    }

    public function alerts()
    {
        return $this->belongsToMany('Tobuli\Entities\Alert', 'alert_device', 'device_id', 'alert_id')
            ->join('user_device_pivot', function ($join) {
                $join
                    ->on('user_device_pivot.device_id', '=', 'alert_device.device_id')
                    ->on('user_device_pivot.user_id', '=', 'alerts.user_id');
            })
            ->withPivot('started_at', 'fired_at', 'silenced_at', 'active_from', 'active_to');
    }

    public function events()
    {
        return $this->hasMany('Tobuli\Entities\Event', 'device_id');
    }

    public function last_event()
    {
        $query = $this->hasOne('Tobuli\Entities\Event', 'device_id');

        if ($user = getActingUser()) {
            $query->where('user_id', $user->id);
        }

        return $query->orderBy('id', 'desc');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany('Tobuli\Entities\User', 'user_device_pivot', 'device_id', 'user_id')->withPivot('group_id');
    }

    public function driver()
    {
        return $this->hasOne('Tobuli\Entities\UserDriver', 'id', 'current_driver_id');
    }

    public function drivers()
    {
        return $this->hasMany('Tobuli\Entities\UserDriver', 'device_id');
    }

    public function sensors()
    {
        return $this->hasMany('Tobuli\Entities\DeviceSensor', 'device_id');
    }

    public function services()
    {
        return $this->hasMany('Tobuli\Entities\DeviceService', 'device_id');
    }

    public function expenses()
    {
        return $this->hasMany('Tobuli\Entities\DeviceExpense', 'device_id');
    }

    public function timezone()
    {
        return $this->hasOne('Tobuli\Entities\Timezone', 'id', 'timezone_id');
    }

    public function deviceCameras()
    {
        return $this->hasMany('Tobuli\Entities\DeviceCamera', 'device_id');
    }

    public function group()
    {
        return $this->hasOne('Tobuli\Entities\DeviceGroup', 'id', 'group_id');
    }

    public function plans()
    {
        return $this->belongsToMany('Tobuli\Entities\DevicePlan', 'device_device_plan', 'device_id', 'plan_id');
    }

    public function deviceType()
    {
        return $this->hasOne('Tobuli\Entities\DeviceType', 'id', 'device_type_id');
    }

    public function subscriptions()
    {
        return $this->hasManyThrough(
            'Tobuli\Entities\Subscription',
            'Tobuli\Entities\Order',
            'entity_id',
            'order_id',
            'id',
            'id'
        )->where('orders.entity_type', 'device')->active();
    }

    public function sentCommands()
    {
        return $this->belongsTo(SentCommand::class, 'imei', 'device_imei');
    }

    public function setDeviceTypeIdAttribute($value)
    {
        $this->attributes['device_type_id'] = empty($value) ? null : $value;
    }

    public function setTimezoneIdAttribute($value)
    {
        $this->attributes['timezone_id'] = empty($value) ? null : $value;
    }

    public function setFuelMeasurementIdAttribute($value)
    {
        $this->attributes['fuel_measurement_id'] = empty($value) ? null : $value;
    }

    public function setForwardAttribute($value)
    {
        if (Arr::get($value, 'active'))
            $this->attributes['forward'] = json_encode($value);
        else
            $this->attributes['forward'] = null;
    }

    public function getForwardAttribute($value)
    {
        return json_decode($value, TRUE);
    }

    public function isSimExpired()
    {
        static $display_sim_expired = null;

        if (is_null($display_sim_expired)) {
            $display_sim_expired = settings('plugins.display_sim_expired.status');
        }

        if (!$display_sim_expired)
            return false;

        if (!$this->hasSimExpireDate())
            return false;

        return strtotime($this->sim_expiration_date) < time();
    }

    public function hasSimExpireDate()
    {
        if (!$this->sim_expiration_date)
            return false;

        if ($this->sim_expiration_date == '0000-00-00')
            return false;

        return true;
    }

    public function isExpiredWithoutExtra()
    {
        if (!$this->hasExpireDate())
            return false;

        return strtotime($this->expiration_date) < time();
    }

    public function isExpired(): bool
    {
        if (!$this->isExpiredWithoutExtra()) {
            return false;
        }

        static $extraTime = null;

        if (is_null($extraTime))
            $extraTime = settings('main_settings.extra_expiration_time');

        return !$extraTime
            || \Carbon::parse($this->expiration_date)->addSeconds($extraTime)->getTimestamp() <= time();
    }

    public function hasExpireDate()
    {
        if (!$this->expiration_date)
            return false;

        if ($this->expiration_date == '0000-00-00 00:00:00')
            return false;

        if ($this->expiration_date == '0000-00-00')
            return false;

        return true;
    }

    public function isPlanAble()
    {
        static $enable_device_plans = null;

        if (is_null($enable_device_plans))
            $enable_device_plans = settings('main_settings.enable_device_plans');

        if (!$enable_device_plans)
            return false;

        if (!$this->hasExpireDate())
            return false;

        return true;
    }

    public function isConnected()
    {
        return Redis::connection('process')->get('connected.' . $this->imei) ? true : false;
    }

    public function getParameter($key, $default = null)
    {
        $parameters = $this->getParameters();

        return array_key_exists($key, $parameters) ? $parameters[$key] : $default;
    }

    public function setParameter($key, $value)
    {
        $parameters = $this->getParameters();

        $parameters[$key] = $value;

        $this->setParameters($parameters);
    }

    /**
     * In new Traccar architecture, device-level parameters are stored in
     * the devices.parameters JSON column (web DB), not in tc_devices.
     */
    public function setParameters($value)
    {
        if (is_array($value)) {
            $value = json_encode($value);
        }

        $this->attributes['parameters'] = $value;
    }

    /**
     * Read parameters from the latest position's attributes JSON.
     * Falls back to devices.parameters (web DB) if no position.
     */
    public function getParameters()
    {
        if ($this->traccar) {
            $position = $this->traccar->latestPosition;

            if ($position) {
                $params = $position->parameters;

                if (is_array($params) && !empty($params)) {
                    return $params;
                }
            }
        }

        if (empty($this->attributes['parameters'])) {
            return [];
        }

        $raw = $this->attributes['parameters'];

        if (is_array($raw)) {
            return $raw;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function getSumDistance($from = null, $to = null)
    {
        $query = $this->positions();

        if ($from)
            $query->where('fixtime', '>', $from);

        if ($to)
            $query->where('fixtime', '<', $to);

        $sum = 0;

        foreach ($query->cursor() as $position) {
            $distance = $position->getParameter('distance');

            if (is_numeric($distance)) {
                $sum += (float) $distance;
            }
        }

        return $sum;
    }

    public function getTotalDistance()
    {
        $distance = $this->getParameter('totaldistance') / 1000;

        return Formatter::distance()->format($distance);
    }

    /**
     * Current speed in km/h.
     *
     * Only a genuinely OFFLINE device reports 0. STATUS_ACK ("device sent
     * something after its last position") must not zero the speed, otherwise a
     * moving vehicle shows 0 whenever tc_devices.lastupdate is newer than the
     * last position's servertime.
     *
     * Source chain: TraccarDevice::speed -> latestPosition -> TraccarPosition::getSpeedAttribute()
     * (knots -> km/h conversion happens there, once).
     */
    public function getSpeed($position = null)
    {
        $speed = 0;

        if (is_null($position) && $this->getTimeoutStatus() === self::STATUS_OFFLINE)
            return $speed;

        $sensor = $this->getSpeedSensor();

        if ($sensor) {
            $speed = $sensor->getValuePosition(
                is_null($position) ? $this->positionTraccar() : $position
            );
        } else {
            $speed = is_null($position) ? ($this->traccar->speed ?? 0) : ($position->speed ?? 0);
        }

        return $speed;
    }

    public function getTimeoutStatus()
    {
        static $minutes = null;

        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout') * 60;

        $serverTime = strtotime($this->getServerTime());

        if ($serverTime < time() - $minutes)
            return self::STATUS_OFFLINE;

        $ackTime = strtotime($this->getAckTime());

        // In new Traccar, lastupdate (ack_time) is written separately from the
        // position's servertime, so allow a small tolerance before calling it ACK.
        return ($serverTime + self::ACK_TOLERANCE) >= $ackTime ? self::STATUS_ONLINE : self::STATUS_ACK;
    }

    /**
     * Determine if the device has actually changed GPS position recently.
     * Compares the spread of positions in the given time window.
     * This is needed because some trackers report a non-zero speed value
     * even when the vehicle is parked (no coordinate change).
     */
    public function hasMovedRecently($seconds = 300, $minMeters = 30)
    {
        if (!$this->traccar_device_id) {
            return false;
        }

        $since = date('Y-m-d H:i:s', time() - $seconds);

        $positions = \Tobuli\Entities\TraccarPosition::where('deviceid', $this->traccar_device_id)
            ->where('servertime', '>=', $since)
            ->orderBy('servertime', 'asc')
            ->limit(50)
            ->get(['latitude', 'longitude']);

        if ($positions->count() < 2) {
            return false;
        }

        $latitudes  = $positions->pluck('latitude')->toArray();
        $longitudes = $positions->pluck('longitude')->toArray();

        $latSpread = max($latitudes) - min($latitudes);
        $lngSpread = max($longitudes) - min($longitudes);

        $avgLat = array_sum($latitudes) / count($latitudes);

        // Convert degrees to meters (approximate)
        $latMeters = $latSpread * 111000;
        $lngMeters = $lngSpread * 111000 * cos(deg2rad($avgLat));

        $distance = sqrt($latMeters * $latMeters + $lngMeters * $lngMeters);

        return $distance >= $minMeters;
    }

    public function getStatusAttribute()
    {
        return $this->getStatus();
    }

    public function getStatus()
    {
        if (!$this->active)
            return self::STATUS_OFFLINE;

        if ($this->getBlockedStatus()) {
            return self::STATUS_BLOCKED;
        }

        if ($this->isExpired())
            return self::STATUS_OFFLINE;

        $status = $this->getTimeoutStatus();

        if ($status == self::STATUS_OFFLINE)
            return $status;

        $speed = $this->getSpeed();

        if ($speed >= $this->min_moving_speed && $this->hasMovedRecently())
            return self::STATUS_ONLINE;

        // Engine on but not moving → IDLE
        if ($engine = $this->getEngineStatus()) {
            return self::STATUS_ENGINE;
        }

        return self::STATUS_ACK;
    }

    public function getStatusColorAttribute()
    {
        return $this->getStatusColor();
    }

    public function getStatusColor($status = null)
    {
        if (is_null($status)) {
            $status = $this->getStatus();
        }

        switch ($status) {
            case self::STATUS_ONLINE:
                $icon_status = 'moving';
                break;
            case self::STATUS_ACK:
                $icon_status = 'stopped';
                break;
            case self::STATUS_ENGINE:
                $icon_status = 'engine';
                break;
            case self::STATUS_BLOCKED:
                $icon_status = 'blocked';
                break;
            default:
                $icon_status = 'offline';
        }

        return Arr::get($this->icon_colors, $icon_status) ?? settings('device.status_colors.colors.' . $icon_status);
    }

    public function getSensorsByType($type)
    {
        if (empty($this->sensors))
            return null;

        if (!is_array($type)) {
            $type = (array)$type;
        }

        return $this->sensors->filter(function ($sensor) use ($type) {
            return in_array($sensor->type, $type);
        });
    }

    public function getSensorByType($type)
    {
        $sensors = $this->sensors;

        if (empty($sensors))
            return null;

        foreach ($sensors as $sensor) {
            if ($sensor['type'] == $type) {
                $type_sensor = $sensor;
                break;
            }
        }

        if (empty($type_sensor))
            return null;

        return $type_sensor;
    }

    public function getRfidSensor()
    {
        return $this->getSensorByType('rfid');
    }

    public function getAnonymizerSensor()
    {
        return config('addon.sensor_type_anonymizer')
            ? $this->getSensorByType('anonymizer')
            : null;
    }

    public function getFuelTankSensor()
    {
        return $this->getSensorByType('fuel_tank');
    }

    public function getLoadSensor()
    {
        return $this->getSensorByType('load');
    }

    public function getOdometerSensor()
    {
        return $this->getSensorByType('odometer');
    }

    public function getIgnitionSensor()
    {
        return $this->getSensorByType('ignition');
    }

    public function getSpeedSensor()
    {
        $detect_speed = $this->detect_speed ?? null;

        if ($detect_speed != 'speed_ecm')
            return null;

        return $this->getSensorByType('speed_ecm');
    }

    public function getEngineHoursSensor()
    {
        return $this->getSensorByType('engine_hours');
    }

    public function getEngineSensor()
    {
        $detect_engine = $this->getEngineDetect();

        if (empty($detect_engine))
            return null;

        return $this->getSensorByType($detect_engine);
    }

    public function getEngineStatusAttribute()
    {
        return $this->getEngineStatus();
    }

    public function getEngineStatus($formated = false)
    {
        $value = $this->getEngineStatusByTimestamps();

        if (is_null($value))
            return $formated ? '-' : null;

        if ($value && $this->getTimeoutStatus() == self::STATUS_OFFLINE)
            $value = false;

        if ($formated)
            return $value ? trans('front.on') : trans('front.off');

        return (bool)$value;
    }

    protected function getEngineDetect()
    {
        $detect_engine = $this->engine_hours == 'engine_hours' ? $this->detect_engine : $this->engine_hours;

        if (empty($detect_engine))
            return null;

        if ($detect_engine == 'gps')
            return null;

        return $detect_engine;
    }

    protected function getEngineStatusByTimestamps()
    {
        $detect_engine = $this->getEngineDetect();

        if (empty($detect_engine))
            return null;

        if (!$this->traccar)
            return null;

        // New architecture: approximate engine from latest position's parameters
        // or from tc_devices.motionstate.
        $position = $this->traccar->latestPosition;

        if ($position) {
            $engine = $position->getParameter('engine');

            if (!is_null($engine)) {
                return filter_var($engine, FILTER_VALIDATE_BOOLEAN);
            }

            $ignition = $position->getParameter('ignition');

            if (!is_null($ignition)) {
                return filter_var($ignition, FILTER_VALIDATE_BOOLEAN);
            }
        }

        // Fallback: motionstate
        return (int) ($this->traccar->motionstate ?? 0) === 1;
    }

    protected function getEngineStatusBySensor()
    {
        $sensor = $this->getEngineSensor();

        if (empty($sensor))
            return null;

        if ($this->getTimeoutStatus() == self::STATUS_OFFLINE)
            return false;

        $value = $sensor->getValueCurrent($this)->getValue();

        return (bool)$value;
    }

    protected function getBlockedStatus(): bool
    {
        $sensor = $this->getBlockedSensor();

        return $sensor && $sensor->getValueCurrent($this)->getValue();
    }

    protected function getBlockedSensor(): ?DeviceSensor
    {
        return Blocked::isEnabled()
            ? $this->getSensorByType(Blocked::getType())
            : null;
    }

    public function getDistanceBetween($dateFrom, $dateTo)
    {
        if (!$this->traccar) {
            return 0;
        }

        $odometer = $this->getOdometerSensor();

        $query = $this->positions()->whereBetween('fixtime', [$dateFrom, $dateTo])->limit(1);

        $first     = (clone $query)->orderBy('fixtime', 'asc');
        $positions = (clone $query)->orderBy('fixtime', 'desc')->union($first)->get();

        if ($positions->count() < 2)
            return 0;

        if (!is_null($odometer) && $odometer->shown_value_by != 'virtual_odometer') {
            $to = (float)$odometer->getValuePosition($positions[0]);
            $from = (float)$odometer->getValuePosition($positions[1]);

            $distance = (empty($from) || empty($to)) ? 0 : $to - $from;
        } else {
            $distance = $positions[0]->getParameter('totaldistance') - $positions[1]->getParameter('totaldistance');

            //from meters to kilometers
            $distance = $distance / 1000;
        }

        return ($distance > 0) ? $distance : 0;
    }

    public function getAddressAttribute()
    {
        if (!$this->traccar) {
            return null;
        }

        $position = $this->traccar->latestPosition;

        return $position ? $position->address : null;
    }

    public function getProtocolAttribute()
    {
        return $this->traccar->protocol ?? null;
    }

    public function getDeviceTime()
    {
        return $this->traccar->device_time ?? null;
    }

    public function getTime()
    {
        return $this->traccar->time ?? null;
    }

    public function getAckTime()
    {
        return $this->traccar->ack_time ?? '';
    }

    public function getServerTime()
    {
        return $this->traccar->server_time ?? '';
    }

    public function getTimeAttribute()
    {
        if (!$this->active)
            return trans('front.disabled');

        if ($this->isSimExpired())
            return trans('front.sim_expired');

        if ($this->isExpired())
            return trans('front.expired');

        $time = max($this->getTime(), $this->getAckTime());

        if (empty($time) || substr($time, 0, 4) == '0000')
            return trans('front.not_connected');

        return Formatter::time()->human($time);
    }

    public function getOnlineAttribute()
    {
        return $this->getStatus();
    }

    public function getLatAttribute()
    {
        if ($this->isExpired())
            return null;

        return $this->latitude;
    }

    public function getLngAttribute()
    {
        if ($this->isExpired())
            return null;

        return $this->longitude;
    }

    public function getLatitudeAttribute()
    {
        if (($anonymizer = $this->getAnonymizerSensor()) && $anonymizer->getValueCurrent($this)->getValue()) {
            return $anonymizer->data['anon_latitude'] ?? null;
        }

        $lat = $this->traccar->lastValidLatitude ?? null;

        return !is_null($lat) ? cord($lat) : null;
    }

    public function getLongitudeAttribute()
    {
        if (($anonymizer = $this->getAnonymizerSensor()) && $anonymizer->getValueCurrent($this)->getValue()) {
            return $anonymizer->data['anon_longitude'] ?? null;
        }

        $lng = $this->traccar->lastValidLongitude ?? null;

        return !is_null($lng) ? cord($lng) : null;
    }

    public function getCourseAttribute()
    {
        $course = 0;

        if (isset($this->traccar->course))
            $course = $this->traccar->course;

        return round($course);
    }

    public function getAltitudeAttribute()
    {
        $altitude = 0;

        if (isset($this->traccar->altitude))
            $altitude = $this->traccar->altitude;

        return Formatter::altitude()->format($altitude);
    }

    /**
     * Tail is computed on-demand from the latest N tc_positions rows.
     * No longer stored in a latest_positions column.
     */
    public function getTailAttribute()
    {
        $length = (int) $this->tail_length;

        if ($length <= 0)
            return [];

        if (!$this->traccar)
            return [];

        $positions = $this->traccar->positions()
            ->orderBy('fixtime', 'desc')
            ->orderBy('id', 'desc')
            ->limit($length)
            ->get();

        $tail = [];

        foreach ($positions as $position) {
            $tail[] = [
                'lat' => $position->latitude,
                'lng' => $position->longitude,
            ];
        }

        // Old behavior: oldest first, newest last
        return array_reverse($tail);
    }

    public function getLatestPositionsAttribute()
    {
        // latest_positions column no longer exists
        return null;
    }

    public function getTimestampAttribute()
    {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
    }

    public function getServerTimestampAttribute()
    {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->server_time) ? strtotime($this->traccar->server_time) : 0;
    }

    public function getAckTimestampAttribute()
    {
        if ($this->isExpired())
            return 0;

        return isset($this->traccar->ack_time) ? strtotime($this->traccar->ack_time) : 0;
    }

    public function getAckTimeAttribute()
    {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->ack_time) ? $this->traccar->ack_time : null;
    }

    public function getServerTimeAttribute()
    {
        if ($this->isExpired())
            return null;

        return isset($this->traccar->server_time) ? $this->traccar->server_time : null;
    }

    public function getMovedAtAttribute()
    {
        if ($this->isExpired())
            return null;

        if (!$this->traccar)
            return null;

        // Approximate: motiontime when currently not moving
        return $this->traccar->motiontime ?? null;
    }

    public function getMovedTimestampAttribute()
    {
        return $this->moved_at ? strtotime($this->moved_at) : 0;
    }

    public function getLastConnectTimeAttribute()
    {
        $lastConnect = $this->getLastConnectTimestampAttribute();

        return $lastConnect ? date('Y-m-d H:i:s', $lastConnect) : null;
    }

    public function getLastConnectTimestampAttribute()
    {
        return max($this->server_timestamp, $this->ack_timestamp);
    }

    public function getOtherAttribute()
    {
        return $this->traccar->attributes ?? null;
    }

    public function getSpeedAttribute()
    {
        return Formatter::speed()->format($this->getSpeed());
    }

    public function getIdleDuration()
    {
        if (!$this->traccar)
            return 0;

        // Approximate using motionstate / motiontime
        if ((int) ($this->traccar->motionstate ?? 0) === 1)
            return 0;

        $motiontime = $this->traccar->motiontime ?? null;

        if (!$motiontime)
            return 0;

        return max(0, time() - strtotime($motiontime));
    }

    public function getIdleDurationAttribute()
    {
        $duration = $this->getIdleDuration();

        return Formatter::duration()->human($duration);
    }

    public function getIgnitionDuration()
    {
        if (!$this->traccar)
            return 0;

        // No direct equivalent; approximate with motiontime
        if ((int) ($this->traccar->motionstate ?? 0) !== 1)
            return 0;

        $motiontime = $this->traccar->motiontime ?? null;

        if (!$motiontime)
            return 0;

        return max(0, time() - strtotime($motiontime));
    }

    public function getIgnitionDurationAttribute()
    {
        $duration = $this->getIgnitionDuration();

        return Formatter::duration()->human($duration);
    }

    public function getStopDuration()
    {
        if (!$this->traccar)
            return null;

        if ((int) ($this->traccar->motionstate ?? 0) === 1)
            return null;

        $motiontime = $this->traccar->motiontime ?? null;

        if (!$motiontime)
            return null;

        return max(0, time() - strtotime($motiontime));
    }

    public function getStopDurationAttribute()
    {
        $duration = $this->getStopDuration();

        if (is_null($duration))
            return '-';

        if ($duration < 5)
            $duration = 0;

        return Formatter::duration()->human($duration);
    }

    public function getFormatSensors()
    {
        if ($this->isExpired())
            return null;

        $result = [];

        foreach ($this->sensors as $sensor) {
            if (in_array($sensor->type, ['harsh_acceleration', 'harsh_breaking', 'harsh_turning']))
                continue;

            $value = $sensor->getValueCurrent($this);

            $result[] = [
                'id'            => $sensor->id,
                'type'          => $sensor->type,
                'name'          => $sensor->formatName(),
                'show_in_popup' => $sensor->show_in_popup,
                'value'         => htmlspecialchars($value->getFormatted()),
                'val'           => $value->getValue(),
                'scale_value'   => $sensor->getValueScale($value->getValue())
            ];
        }

        return $result;
    }

    public function getFormatServices()
    {
        if ($this->isExpired())
            return null;

        $result = [];

        foreach ($this->services as $service) {
            $service->setSensors($this->sensors);

            $result[] = [
                'id'       => $service->id,
                'name'     => $service->name,
                'value'    => $service->expiration(),
                'expiring' => $service->isExpiring()
            ];
        }

        return $result;
    }

    /**
     * No-op. Tail is computed on-demand by getTailAttribute().
     */
    public function generateTail()
    {
        return;
    }

    public function applyPositionsTimezone()
    {
        if (!$this->timezone_id || $this->timezone_id == 57) {
            $value = 'devicetime';
        } else {
            list($hours, $minutes) = explode(' ', $this->timezone->time);

            if ($this->timezone->prefix == 'plus')
                $value = "DATE_ADD(devicetime, INTERVAL '$hours:$minutes' HOUR_MINUTE)";
            else
                $value = "DATE_SUB(devicetime, INTERVAL '$hours:$minutes' HOUR_MINUTE)";
        }

        // Only update positions; tc_devices has no time column anymore.
        $this->positions()->update(['fixtime' => DB::raw($value)]);
    }

    public function isCorrectUTC()
    {
        $change = 900; //15 mins

        $server_time = strtotime($this->getServerTime());
        $device_time = strtotime($this->getDeviceTime());

        if ($server_time && (abs($server_time - $device_time) < $change))
            return true;

        return false;
    }

    public function canChat()
    {
        if ($this->app_tracker_login) {
            return true;
        }

        return $this->protocol == 'osmand';
    }

    public function scopeFilterGroupId($query, $group_id)
    {
        $query->where('user_device_pivot.group_id', $group_id ?: 0);
    }

    public function scopeVisible($query)
    {
        $query->where('user_device_pivot.active', 1);
    }

    public function scopeNPerGroup($query, $group, $n = 10)
    {
        $table = ($this->getTable());

        $query->from(DB::raw("(SELECT @rank:=0, @group:=0) as vars, {$table}"));

        if (!$query->getQuery()->columns) {
            $query->select("{$table}.*");
        }

        $groupAlias = 'group_' . md5(time());
        $rankAlias  = 'rank_' . md5(time());

        $query->addSelect(DB::raw(
            "@rank := IF(@group = {$group}, @rank+1, 1) as {$rankAlias}, @group := {$group} as {$groupAlias}"
        ));

        $query->getQuery()->orders = (array) $query->getQuery()->orders;
        array_unshift($query->getQuery()->orders, ['column' => $group, 'direction' => 'asc']);

        $subQuery = $query->toSql();

        $newBase = $this->newQuery()
            ->from(DB::raw("({$subQuery}) as {$table}"))
            ->mergeBindings($query->getQuery())
            ->where($rankAlias, '<=', $n)
            ->getQuery();

        $query->setQuery($newBase);
    }

    public function changeDriver($driver, $time = null, $withoutAlerts = false)
    {
        if (is_null($time))
            $time = date('Y-m-d H:i:s');

        $this->current_driver_id = $driver->id ?? null;
        $this->save();

        DB::table('user_driver_position_pivot')->insert([
            'device_id' => $this->id,
            'driver_id' => $driver->id ?? null,
            'date'      => $time
        ]);

        if (!$driver || $withoutAlerts)
            return;

        $position = $this->positionTraccar();

        if (is_null($position))
            return;

        $alerts = $this->alerts->filter(function ($item) {
            return $item->type == 'driver';
        });

        foreach ($alerts as $alert) {
            $event = $this->events()->make([
                'type'         => 'driver',
                'user_id'      => $alert->user_id,
                'alert_id'     => $alert->id,
                'device_id'    => $this->id,
                'geofence_id'  => null,
                'position_id'  => $position->id,
                'altitude'     => $position->altitude,
                'course'       => $position->course,
                'latitude'     => $position->latitude,
                'longitude'    => $position->longitude,
                'speed'        => $position->speed,
                'time'         => $position->time,
                'message'      => $driver->name,
                'additional'   => [
                    'driver_id'   => $driver->id,
                    'driver_name' => $driver->name
                ]
            ]);

            $event->channels = $alert->channels;

            (new EventWriteService())->write([$event]);
        }
    }

    public function beacons(): BelongsToMany
    {
        return $this->belongsToMany(Device::class, 'device_current_beacons_pivot', 'device_id', 'beacon_id');
    }

    public function setExpirationDateAttribute($value)
    {
        $this->attributes['expiration_date'] = is_null($value) ? '0000-00-00 00:00:00' : $value;
    }

    public function getExpirationDateAttribute($value)
    {
        if ($value == '0000-00-00') {
            return null;
        }

        if ($value == '0000-00-00 00:00:00') {
            return null;
        }

        return $value;
    }

    public function scopeChatable($query)
    {
        return $query->where(function ($q) {
            $q->where('app_tracker_login', 1);
        });
    }

    public function scopeHasExpiration($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('expiration_date');
            $q->where('expiration_date', '!=', '0000-00-00');
            $q->where('expiration_date', '!=', '0000-00-00 00:00:00');
        });
    }

    public function scopeHasntExpiration($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiration_date');
            $q->orWhere('expiration_date', '=', '0000-00-00');
            $q->orWhere('expiration_date', '=', '0000-00-00 00:00:00');
        });
    }

    public function scopeIsExpiringAfter($query, int $days, bool $addExtra = true)
    {
        $dateFrom = Carbon::now();
        $dateTo = Carbon::now()->addDays($days);

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $dateFrom->subSeconds($extraTime);
            $dateTo->subSeconds($extraTime);
        }

        return $query
            ->hasExpiration()
            ->where('expiration_date', '>=', $dateFrom)
            ->where('expiration_date', '<=', $dateTo);
    }

    public function scopeIsExpiredBefore($query, int $days, bool $addExtra = true)
    {
        $date = Carbon::now()->subDays($days);

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query
            ->hasExpiration()
            ->where('expiration_date', '<=', $date);
    }

    public function scopeExpired($query, bool $addExtra = true)
    {
        $date = Carbon::now();

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query
            ->hasExpiration()
            ->where('expiration_date', '<=', $date);
    }

    public function scopeExpiredForLastDays($query, int $days = 0, bool $addExtra = true)
    {
        $date = Carbon::now()->subDays($days);

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query->expired()->where('expiration_date', '>=', $date);
    }

    public function scopeUnexpired($query, bool $addExtra = true)
    {
        $date = Carbon::now();

        if ($addExtra && ($extraTime = settings('main_settings.extra_expiration_time'))) {
            $date->subSeconds($extraTime);
        }

        return $query
            ->where(function ($q) use ($date) {
                $q->hasntExpiration();
                $q->orWhere(function ($q2) use ($date) {
                    $q2->hasExpiration()->where('expiration_date', '>', $date);
                });
            });
    }

    public function scopeHasSimExpiration($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('sim_expiration_date');
            $q->where('sim_expiration_date', '!=', '0000-00-00');
            $q->where('sim_expiration_date', '!=', '0000-00-00 00:00:00');
        });
    }

    public function scopeIsSimExpiringAfter($query, $days)
    {
        return $query
            ->hasSimExpiration()
            ->where('sim_expiration_date', '>=', Carbon::now())
            ->where('sim_expiration_date', '<=', Carbon::now()->addDays($days));
    }

    public function scopeIsSimExpiredBefore($query, $days)
    {
        return $query
            ->hasSimExpiration()
            ->where('sim_expiration_date', '<=', Carbon::now()->subDays($days));
    }

    public function scopeFilterUserAbility($query, User $user, $ability = 'own')
    {
        return $query->with('users')->get()->filter(function ($device) use ($user, $ability) {
            return $user->can($ability, $device);
        });
    }

    public function scopeWhereIdOrImei(Builder $query, $value)
    {
        return $query->where(function (Builder $query) use ($value) {
            $query->where('id', $value);
            $query->orWhere('imei', $value);
        });
    }

    public static function getFields()
    {
        $fields = [
            'id' => trans('validation.attributes.id'),
            'name' => trans('validation.attributes.name'),
            'imei' => trans('validation.attributes.imei'),
            'sim_number' => trans('validation.attributes.sim_number'),
            'vin' => trans('validation.attributes.vin'),
            'device_model' => trans('validation.attributes.device_model'),
            'plate_number' => trans('validation.attributes.plate_number'),
            'registration_number' => trans('validation.attributes.registration_number'),
            'object_owner' => trans('validation.attributes.object_owner'),
            'additional_notes' => trans('validation.attributes.additional_notes'),

            'users_emails' => trans('admin.users'),
            'protocol' => trans('front.protocol'),
            'latitude' => trans('front.latitude'),
            'longitude' => trans('front.longitude'),
            'altitude' => trans('front.altitude'),
            'course' => trans('front.course'),
            'speed' => trans('front.speed'),
            'last_connect_time' => trans('admin.last_connection'),
            'stop_duration' => trans('front.stop_duration'),

            'expiration_date' => trans('validation.attributes.expiration_date'),
        ];

        if (settings('plugins.additional_installation_fields.status')) {
            $fields['sim_activation_date'] = trans('validation.attributes.sim_activation_date');
            $fields['sim_expiration_date'] = trans('validation.attributes.sim_expiration_date');
            $fields['installation_date']   = trans('validation.attributes.installation_date');
        }

        return $fields;
    }

    public function getUsersEmailsAttribute()
    {
        return $this
            ->users
            ->filter(function ($user) {
                return auth()->user()->can('show', $user);
            })
            ->implode('email', ', ');
    }

    public function getImageAttribute()
    {
        return (new DeviceImageService($this))->get();
    }

    public function getPlanAttribute()
    {
        return $this->plans->first() ?? null;
    }

    public function getPlanIdAttribute()
    {
        return $this->plan->id ?? null;
    }

    public function getNameWithSimNumberAttribute()
    {
        return $this->name . " ({$this->sim_number})";
    }

    public function isMove()
    {
        return $this->getStatus() == self::STATUS_ONLINE;
    }

    public function isIdle()
    {
        return $this->getStatus() == self::STATUS_ENGINE;
    }

    public function isStop()
    {
        return $this->getStatus() == self::STATUS_ACK;
    }

    public function isOffline()
    {
        return $this->getTimeoutStatus() === self::STATUS_OFFLINE;
    }

    public function isPark()
    {
        return $this->isStop() && !$this->isIdle();
    }

    public function isOfflineFrom($date)
    {
        $time = strtotime(max($this->getServerTime(), $this->getAckTime()));

        return Carbon::parse($date)->timestamp > $time;
    }

    public function isInactive()
    {
        $time = strtotime(max($this->getServerTime(), $this->getAckTime()));

        return Carbon::now()->subMinutes(settings('main_settings.default_object_inactive_timeout'))->timestamp > $time;
    }

    public function isNeverConnected()
    {
        return is_null($this->getServerTime()) && is_null($this->getAckTime());
    }

    public function wasConnected()
    {
        return !$this->isNeverConnected();
    }

    /**
     * Legacy scope kept as a no-op for backward compat.
     * New Traccar architecture uses whereIn(traccar_device_id) subqueries instead.
     */
    public function scopeTraccarJoin($query)
    {
        $selects = $query->getQuery()->columns;

        if (!($selects && in_array('devices.*', $selects))) {
            $query->select('devices.*');
        }

        return $query;
    }

    public function scopeWasConnected($query)
    {
        $ids = TraccarDevice::whereNotNull('lastupdate')->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeNeverConnected($query)
    {
        $ids = TraccarDevice::whereNull('lastupdate')->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeConnectedAfter($query, $time)
    {
        $ids = TraccarDevice::where('lastupdate', '>=', $time)->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeConnectedBefore($query, $time)
    {
        $ids = TraccarDevice::where('lastupdate', '<', $time)->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeUpdatedAfter($query, $time)
    {
        $ids = TraccarDevice::where('lastupdate', '>=', $time)->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeUpdatedBefore($query, $time)
    {
        $ids = TraccarDevice::where('lastupdate', '<', $time)->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeOnline($query, $minutes = null)
    {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout');

        $time = Carbon::now()->subMinutes($minutes);

        return $query->connectedAfter($time);
    }

    public function scopeOffline($query, $minutes = null)
    {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_online_timeout');

        $time = Carbon::now()->subMinutes($minutes);

        $ids = TraccarDevice::where('lastupdate', '<', $time)->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeInactive($query, $minutes = null)
    {
        if (is_null($minutes))
            $minutes = settings('main_settings.default_object_inactive_timeout');

        return $query->offline($minutes);
    }

    public function scopeMove($query)
    {
        $time = Carbon::now()->subMinutes(settings('main_settings.default_object_online_timeout'));

        $ids = TraccarDevice::where('lastupdate', '>=', $time)
            ->where('motionstate', 1)
            ->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeStop($query)
    {
        $time = Carbon::now()->subMinutes(settings('main_settings.default_object_online_timeout'));

        $ids = TraccarDevice::where('lastupdate', '>=', $time)
            ->where(function ($q) {
                $q->where('motionstate', 0)->orWhereNull('motionstate');
            })
            ->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeStopDuration($query, $minutes)
    {
        $time = Carbon::now()->subMinutes($minutes);

        $ids = TraccarDevice::where('motionstate', 0)
            ->whereNotNull('motiontime')
            ->where('motiontime', '<', $time)
            ->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopePark($query)
    {
        return $query->stop()->online();
    }

    public function scopeIdle($query)
    {
        return $query->stop()->online();
    }

    public function scopeEngineOn($query)
    {
        $time = Carbon::now()->subMinutes(settings('main_settings.default_object_online_timeout'));

        $ids = TraccarDevice::where('lastupdate', '>=', $time)
            ->where('motionstate', 1)
            ->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeEngineOff($query)
    {
        $time = Carbon::now()->subMinutes(settings('main_settings.default_object_online_timeout'));

        $ids = TraccarDevice::where('lastupdate', '>=', $time)
            ->where(function ($q) {
                $q->where('motionstate', 0)->orWhereNull('motionstate');
            })
            ->pluck('id');

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeProtocol($query, $protocol)
    {
        if (is_null($protocol)) {
            $ids = TraccarPosition::whereNull('protocol')->distinct()->pluck('deviceid');
        } elseif (is_array($protocol)) {
            $ids = TraccarPosition::whereIn('protocol', $protocol)->distinct()->pluck('deviceid');
        } else {
            $ids = TraccarPosition::where('protocol', $protocol)->distinct()->pluck('deviceid');
        }

        return $query->whereIn('devices.traccar_device_id', $ids);
    }

    public function scopeGroupProtocols($query)
    {
        return $query->with('traccar');
    }

    /**
     * Simplified in new architecture: cross-connection geofence queries are
     * evaluated in PHP by checking each device's latest position.
     *
     * @param  Builder  $query
     * @param int|int[] $ids
     * @return Builder
     */
    public function scopeInGeofences(Builder $query, $ids): Builder
    {
        if (!is_array($ids)) {
            $ids = (array)$ids;
        }

        if (empty($ids)) {
            return $query->whereRaw('1 = 0');
        }

        $geofences = Geofence::whereIn('id', $ids)->get();

        if ($geofences->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $traccarDevices = TraccarDevice::with('latestPosition')->get();

        $matchingIds = [];

        foreach ($traccarDevices as $td) {
            $pos = $td->latestPosition;

            if (!$pos || is_null($pos->latitude) || is_null($pos->longitude)) {
                continue;
            }

            foreach ($geofences as $geofence) {
                $coords = $geofence->coordinates;

                if (is_string($coords)) {
                    $coords = json_decode($coords, true);
                }

                if (!is_array($coords) || empty($coords)) {
                    continue;
                }

                $lats = array_column($coords, 0);
                $lngs = array_column($coords, 1);

                if (empty($lats) || empty($lngs)) {
                    continue;
                }

                if ($pos->latitude >= min($lats) && $pos->latitude <= max($lats) &&
                    $pos->longitude >= min($lngs) && $pos->longitude <= max($lngs)) {
                    $matchingIds[] = $td->id;
                    break;
                }
            }
        }

        return $query->whereIn('devices.traccar_device_id', $matchingIds);
    }

    public function scopeKindGeneral(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_GENERAL);
    }

    public function scopeKindBeacon(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_BEACON);
    }

    public function isGeneral(): bool
    {
        return $this->kind === self::KIND_GENERAL;
    }

    public function isBeacon(): bool
    {
        return $this->kind === self::KIND_BEACON;
    }

    public function activate(DevicePlan $plan, $expirationDate)
    {
        $this->plans()->sync([$plan->id]);

        $this->update([
            'expiration_date' => $expirationDate,
        ]);

        event(new DeviceSubscriptionActivate($this));
    }

    public function renew($expirationDate)
    {
        $this->setExpirationDate($expirationDate);

        event(new DeviceSubscriptionRenew($this));
    }

    public function setExpirationDate($expirationDate)
    {
        $this->update([
            'expiration_date' => $expirationDate,
        ]);
    }
}