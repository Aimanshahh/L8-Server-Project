<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TraccarPosition extends AbstractEntity
{
    use HasFactory;

    const VIRTUAL_ENGINE_HOURS_KEY = 'enginehours';
    const ENGINE_HOURS_KEY         = 'hours';

    protected $connection = 'traccar_mysql';
    protected $table      = 'tc_positions';

    protected $fillable = [
        'deviceid',
        'protocol',
        'servertime',
        'devicetime',
        'fixtime',
        'valid',
        'latitude',
        'longitude',
        'altitude',
        'speed',
        'course',
        'address',
        'attributes',
        'accuracy',
        'network',
        'geofenceids',
    ];

    public $timestamps = false;

    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static((array) $attributes);
        $model->exists = $exists;
        $model->setConnection($this->getConnectionName());
        $model->setTable($this->getTable());

        return $model;
    }

    public function device()
    {
        return $this->belongsTo(
            'Tobuli\Entities\TraccarDevice',
            'deviceid',
            'id'
        );
    }

    public function scopeLastest($query)
    {
        return $query->orderBy('fixtime', 'desc');
    }

    public function scopeOrderliness($query, $order = 'desc')
    {
        return $query
            ->orderBy('fixtime', $order)
            ->orderBy('id', $order);
    }

    /*
    |--------------------------------------------------------------------------
    | Field aliases (old Laravel fields -> new Traccar fields)
    |--------------------------------------------------------------------------
    */

    public function getTimeAttribute()
    {
        return $this->attributes['fixtime'] ?? null;
    }

    public function setTimeAttribute($value)
    {
        $this->attributes['fixtime'] = $value;
    }

    public function getDeviceTimeAttribute()
    {
        return $this->attributes['devicetime'] ?? null;
    }

    public function setDeviceTimeAttribute($value)
    {
        $this->attributes['devicetime'] = $value;
    }

    public function getServerTimeAttribute()
    {
        return $this->attributes['servertime'] ?? null;
    }

    public function setServerTimeAttribute($value)
    {
        $this->attributes['servertime'] = $value;
    }

    public function getDeviceIdAttribute()
    {
        return $this->attributes['deviceid'] ?? null;
    }

    public function setDeviceIdAttribute($value)
    {
        $this->attributes['deviceid'] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Attributes JSON helpers
    |--------------------------------------------------------------------------
    */

    public function getSpeedAttribute($value)
    {
        return (float) $value;
    }

    public function getParametersAttribute()
    {
        if (empty($this->attributes['attributes'])) {
            return [];
        }

        $value = $this->attributes['attributes'];

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setParametersAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['attributes'] = json_encode($value);
        } else {
            $this->attributes['attributes'] = $value;
        }
    }

    public function hasParameter($key)
    {
        $parameters = $this->parameters;

        return array_key_exists($key, $parameters);
    }

    public function getParameter($key, $default = null)
    {
        $parameters = $this->parameters;

        return array_key_exists($key, $parameters) ? $parameters[$key] : $default;
    }

    public function setParameter($key, $value)
    {
        $parameters = $this->parameters;
        $parameters[$key] = $value;

        $this->parameters = $parameters;
    }

    public function getOtherAttribute()
    {
        return $this->attributes['attributes'] ?? null;
    }

    public function setOtherAttribute($value)
    {
        $this->attributes['attributes'] = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Distance (stored in attributes JSON)
    |--------------------------------------------------------------------------
    */

    public function getDistanceAttribute()
    {
        return $this->getParameter('distance');
    }

    public function setDistanceAttribute($value)
    {
        $this->setParameter('distance', $value);
    }

    public function getTotalDistanceAttribute()
    {
        return $this->getParameter('totaldistance');
    }

    public function setTotalDistanceAttribute($value)
    {
        $this->setParameter('totaldistance', $value);
    }

    public function getPowerAttribute()
    {
        return $this->getParameter('power');
    }

    /*
    |--------------------------------------------------------------------------
    | RFID / sensors
    |--------------------------------------------------------------------------
    */

    public function isRfid($rfid)
    {
        if (empty($rfid)) {
            return false;
        }

        switch ($this->protocol) {
            case 'teltonika':
                return $rfid == $this->rfid || $rfid == $this->rfidRaw;
            default:
                return $rfid == $this->rfid;
        }
    }

    public function getRfids()
    {
        $rfids = [];

        if (!$this->rfidRaw) {
            return $rfids;
        }

        switch ($this->protocol) {
            case 'teltonika':
                $rfids[] = $this->rfid;
                $rfids[] = $this->rfidRaw;
                break;
            case 'meitrack':
                $rfids[] = $this->rfid;
                $rfids[] = $this->rfidRaw;
                break;
            default:
                $rfids[] = $this->rfid;
                break;
        }

        return $rfids;
    }

    public function getRfidAttribute()
    {
        $rfid = $this->getRfidRawAttribute();

        if ($rfid) {
            try {
                switch ($this->protocol) {
                    case 'teltonika':
                        $rfid = teltonikaIbutton($rfid);
                        break;
                    case 'meitrack':
                        $rfid = hexdec($rfid);
                        break;
                }
            } catch (\Exception $e) {
            }
        }

        return (string) $rfid;
    }

    public function getRfidRawAttribute()
    {
        $parameters = $this->parameters;
        $rfid = empty($parameters['rfid']) ? null : $parameters['rfid'];

        if (!$rfid) {
            switch ($this->protocol) {
                case 'teltonika':
                    $rfid = empty($parameters['io78']) ? null : $parameters['io78'];
                    break;
                case 'fox':
                    $rfid = empty($parameters['status-data']) ? null : $parameters['status-data'];
                    break;
                case 'ruptela':
                    $rfid = empty($parameters['io34']) ? null : $parameters['io34'];
                    $rfid = (is_null($rfid) && !empty($parameters['io171'])) ? $parameters['io171'] : $rfid;
                    break;
            }
        }

        if (!$rfid && !empty($parameters['driveruniqueid'])) {
            $rfid = $parameters['driveruniqueid'];
        }

        if (!$rfid && !empty($parameters['driver1'])) {
            $rfid = $parameters['driver1'];
        }

        if (!$rfid && !empty($parameters['beacon1uuid'])) {
            $rfid = $parameters['beacon1uuid'];
        }

        return $rfid;
    }

    public function getSensorsValuesAttribute($value)
    {
        $parameters = $this->parameters;

        return !empty($parameters['sensors_values']) ? $parameters['sensors_values'] : [];
    }

    public function setSensorsValuesAttribute($value)
    {
        $parameters = $this->parameters;
        $parameters['sensors_values'] = $value;
        $this->attributes['attributes'] = json_encode($parameters);
    }

    public function getSensorValue($sensor_id)
    {
        $sensors = $this->sensors_values;

        if (empty($sensors)) {
            return null;
        }

        if (!is_array($sensors)) {
            return null;
        }

        foreach ($sensors as $sensor) {
            if ($sensor['id'] == $sensor_id) {
                return $sensor['val'];
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Validity / engine hours
    |--------------------------------------------------------------------------
    */

    public function isValid()
    {
        return $this->valid > 0 ? true : false;
    }

    public function getVirtualEngineHours()
    {
        return $this->getParameter(self::VIRTUAL_ENGINE_HOURS_KEY, 0);
    }

    public function getEngineHours()
    {
        return floatval($this->getParameter(self::ENGINE_HOURS_KEY, 0)) * 3600;
    }
}