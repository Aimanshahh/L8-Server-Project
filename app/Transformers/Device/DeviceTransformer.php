<?php

namespace App\Transformers\Device;

use App\Transformers\BaseTransformer;
use App\Transformers\Driver\DriverFullTransformer;
use Tobuli\Entities\Device;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class DeviceTransformer extends BaseTransformer {


    protected $availableIncludes = [
        'position',
        'icon',
        'sensors',
        'services',
        'driver',
        'users'
    ];

    public function includePosition(Device $device) {
        return $this->item($device, new DevicePositionTransformer(), false);
    }

    public function includeIcon(Device $device) {
        return $this->item($device, new DeviceIconTransformer(), false);
    }

    public function includeSensors(Device $device) {
        return $this->item($device, new DeviceSensorsTransformer(), false);
    }

    public function includeServices(Device $device) {
        return $this->item($device, new DeviceServicesTransformer(), false);
    }

    public function includeDriver(Device $device) {
        if ( ! $device->driver)
            return null;

        return $this->item($device->driver, new DriverFullTransformer(), false);
    }

    public function includeUsers(Device $device) {
        return $this->item($device, new DeviceUsersTransformer(), false);
    }

    /**
     * Metadata for the exact row used by the live marker. Keeping this in one
     * place stops coordinates, speed, course and time from coming from
     * different positions during a poll.
     */
    protected function livePositionMeta($entity)
    {
        $position = $entity->traccar
            ? $entity->traccar->smartLatestPosition()
            : null;

        return [
            'position_id'   => $position ? (int) $position->id : 0,
            'fix_timestamp' => $position && $position->fixtime ? (int) strtotime($position->fixtime) : 0,
            'valid'         => $position ? (int) $position->valid : 0,
            'accuracy'      => $position && is_numeric($position->accuracy) ? (float) $position->accuracy : null,
        ];
    }

    public function playbackTrack($entity)
    {
        try {
            $tid = (int) $entity->traccar_device_id;
            if ( ! $tid)
                return [];

            $posId = (int) optional($entity->traccar)->positionid;
            $key   = 'mp_track3:' . $tid . ':' . $posId;
            $ttl   = $posId ? 300 : 10;

            return Cache::remember($key, $ttl, function () use ($tid) {
                $rows = DB::connection('traccar_mysql')->table('tc_positions')
                    ->select('id', 'fixtime', 'latitude', 'longitude', 'speed', 'course', 'valid', 'accuracy')
                    ->where('deviceid', $tid)
                    ->where('valid', 1)
                    // Chronological order, not packet-arrival order. A late
                    // packet may have a larger id but an older fix time and
                    // must never be replayed as newer movement.
                    ->orderBy('fixtime', 'desc')
                    ->orderBy('id', 'desc')
                    ->limit(30)
                    ->get();

                return $this->prepareTrack($rows);
            });
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Convert tc_positions rows into the strict, chronological track consumed
     * by the marker engine. Real fixtimes are preserved; same-time bursts are
     * collapsed to their newest row instead of being given fake timestamps.
     */
    protected function prepareTrack($rows)
    {
        $out = [];
        $lastT = null;

        if ( ! is_array($rows)) {
            $rows = method_exists($rows, 'all') ? $rows->all() : (array) $rows;
        }

        usort($rows, function ($a, $b) {
            $ta = strtotime($a->fixtime);
            $tb = strtotime($b->fixtime);

            if ($ta !== $tb) {
                return $ta - $tb;
            }

            return (int) $a->id - (int) $b->id;
        });

        foreach ($rows as $r) {
            $t = strtotime($r->fixtime);

            if ( ! $t || (isset($r->valid) && ! (int) $r->valid))
                continue;

            $lat = (float) $r->latitude;
            $lng = (float) $r->longitude;

            if ( ! is_finite($lat) || ! is_finite($lng) || abs($lat) > 90 || abs($lng) > 180)
                continue;

            if (abs($lat) < 1e-9 && abs($lng) < 1e-9)
                continue;

            $speed  = is_numeric($r->speed) ? round((float) $r->speed * 1.852, 2) : null;
            $course = is_numeric($r->course) && $r->course >= 0 && $r->course <= 360
                ? fmod((float) $r->course, 360)
                : null;

            $point = [
                'id'  => (int) $r->id,
                't'   => $t * 1000,
                'lat' => $lat,
                'lng' => $lng,
                'sp'  => $speed,
                'co'  => $course,
                'a'   => is_numeric($r->accuracy) ? (float) $r->accuracy : null,
            ];

            $prev = end($out);

            if ($prev && $t < $lastT) {
                // Out-of-order packet: it is still stored for history/reports,
                // but it is not part of the live movement track.
                continue;
            }

            if ($prev && $t === $lastT) {
                // A burst of packets sharing one fixtime has no real time to
                // animate across. Keep only the newest row at that instant.
                if ($point['id'] >= $prev['id']) {
                    $out[count($out) - 1] = $point;
                }
                continue;
            }

            if ($prev && abs($prev['lat'] - $lat) < 1e-7 && abs($prev['lng'] - $lng) < 1e-7) {
                // Repeated parked/jitter coordinates: keep the newest report
                // at that spot (time/course/speed), but do not create a segment.
                $out[count($out) - 1] = $point;
                $lastT = $t;
                continue;
            }

            $out[] = $point;
            $lastT = $t;
        }

        return $out;
    }


    /**
     * Returns true if the device's last 10 positions show meaningful
     * coordinate change. Used to filter fake speed values (parked
     * devices that report a nonzero speed from GPS jitter).
     */
    protected function hasReallyMoved($entity, $minMeters = 30)
    {
        if (!$entity->traccar_device_id) return false;

        // Same 300s freshness rule as Device::hasMovedRecently().
        // Bursts then silence must not keep showing a moving speed.
        $ageRow = \DB::connection('traccar_mysql')->selectOne(
            "SELECT TIMESTAMPDIFF(SECOND, MAX(servertime), NOW()) AS age FROM tc_positions WHERE deviceid = ?",
            [$entity->traccar_device_id]
        );
        if (!$ageRow || $ageRow->age === null || (int) $ageRow->age > 300) {
            return false;
        }

        $positions = \Tobuli\Entities\TraccarPosition::where('deviceid', $entity->traccar_device_id)
            ->where('valid', 1)
            ->orderBy('fixtime', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get(['latitude', 'longitude']);

        if ($positions->count() < 3) return true;  // not enough data → trust the tracker

        $lats = $positions->pluck('latitude')->toArray();
        $lngs = $positions->pluck('longitude')->toArray();
        $latSpread = max($lats) - min($lats);
        $lngSpread = max($lngs) - min($lngs);
        $avgLat = array_sum($lats) / count($lats);
        $latM = $latSpread * 111000;
        $lngM = $lngSpread * 111000 * cos(deg2rad($avgLat));
        $distance = sqrt($latM * $latM + $lngM * $lngM);

        return $distance >= $minMeters;
    }
}
