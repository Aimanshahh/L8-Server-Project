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

    public function playbackTrack($entity)
    {
        try {
            $tid = (int) $entity->traccar_device_id;
            if ( ! $tid)
                return [];

            $posId = (int) optional($entity->traccar)->positionid;
            $key   = 'mp_track2:' . $tid . ':' . $posId;
            $ttl   = $posId ? 3600 : 10;

            return Cache::remember($key, $ttl, function () use ($tid) {
                $rows = DB::connection('traccar_mysql')->table('tc_positions')
                    ->select('id', 'fixtime', 'latitude', 'longitude')
                    ->where('deviceid', $tid)
                    ->orderBy('id', 'desc')
                    ->limit(30)
                    ->get();

                $out = [];
                foreach ($rows->reverse() as $r) {
                    $t = strtotime($r->fixtime);
                    if ( ! $t || ! ((float) $r->latitude || (float) $r->longitude))
                        continue;
                    $lat = (float) $r->latitude;
                    $lng = (float) $r->longitude;
                    $prev = end($out);
                    if ($prev && $prev['lat'] === $lat && $prev['lng'] === $lng) {
                        continue;
                    }
                    $out[] = ['id' => (int) $r->id, 't' => $t * 1000, 'lat' => $lat, 'lng' => $lng];
                }
                return $out;
            });
        } catch (\Throwable $e) {
            return [];
        }
    }


    /**
     * Returns true if the device's last 10 positions show meaningful
     * coordinate change. Used to filter fake speed values (parked
     * devices that report a nonzero speed from GPS jitter).
     */
    protected function hasReallyMoved($entity, $minMeters = 30)
    {
        if (!$entity->traccar_device_id) return false;

        $positions = \Tobuli\Entities\TraccarPosition::where('deviceid', $entity->traccar_device_id)
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
