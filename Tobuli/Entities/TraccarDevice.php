<?php namespace Tobuli\Entities; use Carbon\Carbon; class TraccarDevice extends AbstractEntity { protected $connection = 'traccar_mysql'; protected $table = 'tc_devices'; protected $fillable = [ 'name', 'uniqueid', 'lastupdate', 'positionid', 'groupid', 'attributes', 'phone', 'model', 'contact', 'category', 'disabled', 'status', 'expirationtime', 'motionstate', 'motiontime', 'motiondistance', 'overspeedstate', 'overspeedtime', 'overspeedgeofenceid', 'motionstreak', 'calendarid', 'motionpositionid', 'motionlatitude', 'motionlongitude', ]; public $timestamps = false; /* * All positions belonging to this Traccar device */ public function positions() { return $this->hasMany( TraccarPosition::class, 'deviceid', 'id' ); } /* * Current/latest position * * tc_devices.positionid points to tc_positions.id */ public function latestPosition() { return $this->hasOne( TraccarPosition::class, 'id', 'positionid' ); }

        /**
         * Smart latest position.
         *
         * Some trackers emit duplicate "parked" positions with speed=0 on
         * the same IMEI. Traccar sets tc_devices.positionid to the highest
         * ID, which may be one of those parked rows, so the real moving
         * position gets ignored. This method prefers a recent moving
         * position (speed > 0.5 within the last 5 minutes) when the raw
         * latest position has speed 0.
         */
        public function smartLatestPosition()
        {
                $position = $this->latestPosition;

                if (!$position) {
                        return null;
                }

                if ($position->speed > 0.5) {
                        return $position;
                }

                $moving = TraccarPosition::where('deviceid', $this->id)
                        ->where('servertime', '>=', date('Y-m-d H:i:s', time() - 300))
                        ->where('speed', '>', 0.5)
                        ->orderBy('servertime', 'desc')
                        ->first();

                return $moving ?: $position;
        } /* * Old Laravel field: * uniqueId * * New Traccar field: * uniqueid */ public function getUniqueIdAttribute() { return $this->attributes['uniqueid'] ?? null; } /* * Old Laravel field: * latestPosition_id * * New Traccar field: * positionid */ public function getLatestPositionIdAttribute() { return $this->attributes['positionid'] ?? null; } /* * Old Laravel field: * lastValidLatitude */ public function getLastValidLatitudeAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->latitude : ($this->attributes['motionlatitude'] ?? null); } /* * Old Laravel field: * lastValidLongitude */ public function getLastValidLongitudeAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->longitude : ($this->attributes['motionlongitude'] ?? null); } /* * Old Laravel field: * time * * New Traccar: * lastupdate / latest position fixtime */ public function getTimeAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->time : ($this->attributes['lastupdate'] ?? null); } /* * Old Laravel field: * device_time */ public function getDeviceTimeAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->device_time : null; } /* * Old Laravel field: * server_time */ public function getServerTimeAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->server_time : ($this->attributes['lastupdate'] ?? null); } /* * Old Laravel field: * speed */ public function getSpeedAttribute() { $position = $this->smartLatestPosition(); return $position ? (float) $position->speed : 0; } /* * Old Laravel field: * altitude */ public function getAltitudeAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->altitude : 0; } /* * Old Laravel field: * course */ public function getCourseAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->course : 0; } /* * Old Laravel field: * address */ public function getAddressAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->address : null; } /* * Old Laravel field: * protocol */ public function getProtocolAttribute() { $position = $this->smartLatestPosition(); return $position ? $position->protocol : null; } /* * Old Laravel field: * power * * Power/voltage may exist inside Traccar attributes JSON. */ public function getPowerAttribute() { $position = $this->smartLatestPosition(); if (!$position) { return null; } return $position->getParameter('power'); } /* * Old Laravel field: * other * * New Traccar uses attributes JSON. */ public function getOtherAttribute() { return $this->attributes['attributes'] ?? null; } /* * Old Laravel field: * latest_positions * * New Traccar does not store this field. */ public function getLatestPositionsAttribute() { return []; } /* * Old Laravel field: * ack_time * * There is no direct equivalent in new Traccar. */ public function getAckTimeAttribute() { return $this->attributes['lastupdate'] ?? null; } /* * Old Laravel database compatibility. * * New Traccar uses one database, so database_id * is no longer used for selecting position tables. */ public function getDatabaseName() { return config('database.connections.traccar_mysql.database'); } /* * Last connection time */ public function getLastConnectionAttribute() { $timestamp = $this->lastConnectTimestamp; if (!$timestamp) { return null; } return Carbon::createFromTimestamp($timestamp); } public function getLastConnectTimestampAttribute() { $time = $this->attributes['lastupdate'] ?? null; if (!$time) { return null; } return strtotime($time); } }