<?php

namespace Tobuli\Reports\Reports;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\History\Group;
use Tobuli\Entities\DeviceGroup;
use Tobuli\Reports\DeviceHistoryReport;

class GeofenceShiftChangeReport extends DeviceHistoryReport
{
    const TYPE_ID = 88;

    protected $disableFields = ['speed_limit'];
    protected $validation = ['geofences' => 'required'];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.geofence_shift_change');
    }

    protected function getActionsList()
    {
        return [
            DriveStop::class,
            Duration::class,
            Distance::class,

            GroupGeofenceIn::class,
        ];
    }

    protected function isEmptyResult($data)
    {
        return empty($data['groups']) || empty($data['groups']->all());
    }
    protected function generateDevice($device)
    {
        $rows = [];
       

        $data = $this->getDeviceHistoryData($device);

       
            foreach ($data['groups']->all() as $group)
            {
                $rows[] = $this->getDataFromGroup($group, [
                   
                    'start_at',
                    'end_at',
                    'duration',
                    'stop_duration',
                    'distance',
                    'location',
                    'group_geofence'
                ]);
                //$rows['group_d']  = runCacheEntity(DeviceGroup::class, $device->group_id)->implode('title', ', ');
            }
    
            return [
                'meta' => $this->getDeviceMeta($device) + $this->getHistoryMeta($data['root']),
                'table'      => [
                    'rows' => $rows,
                ],
                'totals' => $this->getDataFromGroup($data['groups']->merge(), [
                    'duration',
                    'distance',
                ]),
            ];
    }
  

    
    
}