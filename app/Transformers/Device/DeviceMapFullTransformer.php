<?php

namespace App\Transformers\Device;

use App\Transformers\BaseTransformer;
use Tobuli\Entities\Device;
use Formatter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeviceMapFullTransformer extends DeviceTransformer  {

    protected $defaultIncludes = [
        'icon',
        //'sensors',
        //'services',
        'driver'
    ];

    protected static function requireLoads()
    {
        return ['icon', 'traccar', 'sensors', 'services', 'driver'];
    }

    public function transform(Device $entity)
    {
        $expirationDate = $this->canView($entity, 'expiration_date');
        $expirationDate = $expirationDate ? Formatter::time()->convert($expirationDate) : null;

        $inaccuracy = config('addon.inaccuracy')
            ? $entity->getParameter('inaccuracy')
            : null;

        // Fix time of the latest position (tc_positions.fixtime), used by the
        // frontend to slide the marker over the real gap between two positions.
        $fixTime = $entity->getTime();

        return [
            'id'    => (int)$entity->id,
            'name'  => $entity->name,
            'plate_number'  => $entity->plate_number,
            'device_model'  => $entity->device_model,
            'tail'  => $entity->tail,
            'tail_color' => $entity->tail_color,
            'icon_color' => $entity->getStatusColor(),
            'icon_colors' => $entity->icon_colors,
            'active' => $entity->pivot ? (bool)$entity->pivot->active : null,
            'group_id' => $entity->pivot ? (int)$entity->pivot->group_id : 0,
            'online' => $entity->getStatus(),
            'lat' => $entity->lat,
            'lng' => $entity->lng,
            'speed' => $entity->speed,
            'course' => $entity->course,
            'altitude' => $entity->altitude,
            'time' => $entity->time,
            'timestamp' => (int)$entity->timestamp,
            'fix_timestamp' => $fixTime ? (int)strtotime($fixTime) : 0,
            'track' => $this->playbackTrack($entity),
            'acktimestamp' => (int)$entity->acktimestamp,
            'moved_timestamp' => (int)$entity->moved_timestamp,

            'protocol'        => $this->canView($entity, 'protocol'),
            'expiration_date' => $expirationDate,

            'detect_engine'      => $entity->detect_engine,
            'engine_hours'       => $entity->engine_hours,

            'engine_status'      => $entity->getEngineStatus(),
            'stop_duration'      => $entity->stop_duration,
            'stop_duration_sec'  => $entity->getStopDuration(),
            'total_distance'     => $entity->getTotalDistance(),
            'inaccuracy'         => is_null($inaccuracy) ? null : intval($inaccuracy),

            'sensors'   => $this->sensors($entity),
            'services'  => $entity->getFormatServices(),
        ];
    }

    /**
     * Last fixes (oldest first) with timestamps for frontend marker playback.
     * Cached per latest position id: MySQL is hit only when a device has a new position.
     */
    public function playbackTrack($entity)
    {
        try {
            $tid = (int) $entity->traccar_device_id;
            if ( ! $tid)
                return [];

            $posId = (int) optional($entity->traccar)->positionid;
            $key   = 'mp_track:' . $tid . ':' . $posId;
            $ttl   = $posId ? 3600 : 10;

            return Cache::remember($key, $ttl, function () use ($tid) {
                $rows = DB::connection('traccar_mysql')->table('tc_positions')
                    ->select('id', 'fixtime', 'latitude', 'longitude')
                    ->where('deviceid', $tid)
                    ->orderBy('fixtime', 'desc')
                    ->orderBy('id', 'desc')
                    ->limit(6)
                    ->get();

                $out = [];
                foreach ($rows->reverse() as $r) {
                    $t = strtotime($r->fixtime);
                    if ( ! $t || ! ((float) $r->latitude || (float) $r->longitude))
                        continue;
                    $out[] = ['t' => $t * 1000, 'lat' => (float) $r->latitude, 'lng' => (float) $r->longitude];
                }
                return $out;
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    //tmp
    protected function sensors($entity)
    {
        if ($entity->isExpired())
            return null;

        $result = [];

        foreach ($entity->sensors as $sensor) {
            if (in_array($sensor->type, ['harsh_acceleration', 'harsh_breaking', 'harsh_turning']))
                continue;

            $value = $sensor->getValueCurrent($entity);
            $icon = $value->getIcon();

            $result[] = [
                'id'            => $sensor->id,
                'type'          => $sensor->type,
                'name'          => $sensor->formatName(),
                'show_in_popup' => $sensor->show_in_popup,

                'value'         => htmlspecialchars($value->getFormatted()),
                'val'           => $value->getValue(),
                'scale_value'   => $sensor->getValueScale($value->getValue()),
                'icon'          => $icon ? asset($icon->path) : null
            ];
        }

        return $result;
    }
}