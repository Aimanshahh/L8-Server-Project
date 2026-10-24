<?php namespace Tobuli\Entities;

use Carbon\Carbon;

class TraccarDevice extends AbstractEntity
{
    protected $connection = 'traccar_mysql';
    protected $table = 'tc_devices';

    protected $fillable = [
        'name',
        'uniqueid',
        'lastupdate',
        'positionid',
        'groupid',
        'attributes',
        'phone',
        'model',
        'contact',
        'category',
        'disabled',
        'status',
        'expirationtime',
        'motionstate',
        'motiontime',
        'motiondistance',
        'overspeedstate',
        'overspeedtime',
        'overspeedgeofenceid',
        'motionstreak',
        'calendarid',
        'motionpositionid',
        'motionlatitude',
        'motionlongitude',
    ];

    public $timestamps = false;

    protected $smartPosition = null;
    protected $smartPositionLoaded = false;

    /*
     * All positions belonging to this Traccar device
     */
    public function positions()
    {
        return $this->hasMany(TraccarPosition::class, 'deviceid', 'id');
    }

    /*
     * Current/latest position.
     *
     * tc_devices.positionid points to tc_positions.id. This is the latest
     * packet Traccar stored, which is not guaranteed to be the newest fix by
     * time when packets arrive out of order.
     */
    public function latestPosition()
    {
        return $this->hasOne(TraccarPosition::class, 'id', 'positionid');
    }

    /**
     * Latest usable position for the live map.
     *
     * The old implementation substituted an older "moving" row whenever the
     * newest row had speed <= 0.5. That made stopped/slow vehicles move back
     * to a previous position and reused a stale course, which was the direct
     * cause of the marker jumping backwards and pointing the wrong way.
     *
     * A position is usable when Traccar marked it valid. If the newest packet
     * is invalid, fall back to the most recent valid fix (never to a random
     * moving fix from the last five minutes).
     */
    public function smartLatestPosition()
    {
        if ($this->smartPositionLoaded) {
            return $this->smartPosition;
        }

        // Always resolve by fix time. tc_devices.positionid is packet order,
        // so a late packet can otherwise become the live position.
        $position = TraccarPosition::where('deviceid', $this->id)
            ->where('valid', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw('NOT (latitude = 0 AND longitude = 0)')
            ->orderBy('fixtime', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $this->smartPositionLoaded = true;

        return $this->smartPosition = $position;
    }

    /*
    |--------------------------------------------------------------------------
    | Field aliases (old Laravel fields -> new Traccar fields)
    |--------------------------------------------------------------------------
    */

    public function getUniqueIdAttribute()
    {
        return $this->attributes['uniqueid'] ?? null;
    }

    public function getLatestPositionIdAttribute()
    {
        return $this->attributes['positionid'] ?? null;
    }

    public function getLastValidLatitudeAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->latitude : ($this->attributes['motionlatitude'] ?? null);
    }

    public function getLastValidLongitudeAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->longitude : ($this->attributes['motionlongitude'] ?? null);
    }

    public function getTimeAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->time : ($this->attributes['lastupdate'] ?? null);
    }

    public function getDeviceTimeAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->device_time : null;
    }

    public function getServerTimeAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->server_time : ($this->attributes['lastupdate'] ?? null);
    }

    public function getSpeedAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? (float) $position->speed : 0;
    }

    public function getAltitudeAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->altitude : 0;
    }

    public function getCourseAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->course : 0;
    }

    public function getAddressAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->address : null;
    }

    public function getProtocolAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->protocol : null;
    }

    public function getPowerAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->getParameter('power') : null;
    }

    public function getOtherAttribute()
    {
        return $this->attributes['attributes'] ?? null;
    }

    public function getLatestPositionsAttribute()
    {
        return [];
    }

    public function getAckTimeAttribute()
    {
        return $this->attributes['lastupdate'] ?? null;
    }

    public function getDatabaseName()
    {
        return config('database.connections.traccar_mysql.database');
    }

    public function getLastConnectionAttribute()
    {
        $timestamp = $this->lastConnectTimestamp;

        if ( ! $timestamp) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp);
    }

    public function getLastConnectTimestampAttribute()
    {
        $time = $this->attributes['lastupdate'] ?? null;

        if ( ! $time) {
            return null;
        }

        return strtotime($time);
    }

    /**
     * Expose the live position parameters where sensors expect them.
     */
    public function getParametersAttribute()
    {
        $position = $this->smartLatestPosition();

        return $position ? $position->parameters : [];
    }
}
