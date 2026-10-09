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
            'speed' => $this->hasReallyMoved($entity) ? $entity->speed : 0,
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

    //tmp
    protected function sensors($entity)
    {
        if ($entity->isExpired())
            return null;

        $result = [];
        $other = $entity->other;

        foreach ($entity->sensors as $sensor) {
            if (in_array($sensor->type, ['harsh_acceleration', 'harsh_breaking', 'harsh_turning']))
                continue;

            $value = $sensor->getValueCurrent($entity);

            // GPSWOX sensor pipeline drops the odometer and hours attributes
            // that Traccar stores on every position. Read them straight from
            // the position bag and override the sensor when it returns blank.
            $current = $value->getValue();
            $isBlank = is_null($current) || $current === '-' || $current === 0 || $current === '0';

            if ($isBlank && is_array($other)) {
                if ($sensor->type === 'odometer' && isset($other['odometer'])) {
                    $km = (float) $other['odometer'] / 1000;
                    $value = new class($km, number_format($km, 2) . ' km') {
                        private $v; private $d;
                        public function __construct($v, $d) { $this->v = $v; $this->d = $d; }
                        public function getValue() { return $this->v; }
                        public function getFormatted() { return $this->d; }
                        public function getIcon() { return null; }
                    };
                } elseif ($sensor->type === 'engine_hours' && isset($other['hours'])) {
                    $hours = (float) $other['hours'];
                    $value = new class($hours, number_format($hours, 2) . ' h') {
                        private $v; private $d;
                        public function __construct($v, $d) { $this->v = $v; $this->d = $d; }
                        public function getValue() { return $this->v; }
                        public function getFormatted() { return $this->d; }
                        public function getIcon() { return null; }
                    };
                }
            }

            $icon = method_exists($value, 'getIcon') ? $value->getIcon() : null;

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