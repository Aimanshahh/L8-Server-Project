<?php

namespace Tobuli\Reports\Reports;

use Illuminate\Support\Arr;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\EngineHours;
use Tobuli\History\Actions\Speed;
use Tobuli\History\Actions\GroupGeofenceGroupShifts;
use Tobuli\History\Actions\GroupGeofenceShifts;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

class PerformanceReport extends DeviceHistoryReport
{
    const TYPE_ID = 90;

    public $tableTotals = [];
    public $columns = [];

    protected $disableFields = ['show_addresses', 'speed_limit'];
    protected $validation = ['geofences' => 'required'];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.performance');
    }

    protected function getActionsList()
    {
        return [
            DriveStop::class,
            Duration::class,
            Distance::class,
            Speed::class,
            EngineHours::class,
            GroupGeofenceGroupShifts::class,
            GroupGeofenceShifts::class,
        ];
    }
    protected function beforeGenerate()
    {
        parent::beforeGenerate();

        $this->parameters['shift_start_1'] = '00:00';
        $this->parameters['shift_finish_1'] = '23:59';
    }
    protected function afterGenerate()
    {
      $this->columns['total'] = trans('global.total');
    }
    protected function generateDevice($device)
    {
        $data = $this->getDeviceHistoryData($device);
        
        $table = ['total' => 0];
        
        if ($this->isEmptyResult($data))
            return null;

        $shift = Arr::first(GroupGeofenceShifts::getShifts());

         $this->group->applyArray($data['root']->stats()->only([
            'distance',
            'stop_duration',
           'engine_hours',
            'engine_idle',
            'engine_work'
        ]));

  /** @var Group $group */
  foreach ($data['groups']->all() as $group) {
    $shiftName = GroupGeofenceShifts::getShiftFromGroupName($group->getKey());
    $geofence = GroupGeofenceShifts::getGeofenceFromGroupName($group->getKey());

    if ($shiftName !== $shift['name']) {
        continue;
    }

    if (!isset($table[$geofence])) {
        $table[$geofence] = 0;
    }

    if ($group->stats()->get('stop_count')->value()) {
        $table[$geofence]++;
        $table['total']++;
    }
}
        return [
            'meta' => $this->getDeviceMeta($device) + $this->getHistoryMeta($data['root']),
            'table' => $table,
            'totals' => $this->getDataFromGroup($data['root'], [
                'start_at',
                'end_at',
                'speed_avg',
                'distance',
                'stop_duration',
                'stop_count',
                'engine_hours',
                'engine_idle',
                'engine_work',
            ]),
            
        ];
    }
}