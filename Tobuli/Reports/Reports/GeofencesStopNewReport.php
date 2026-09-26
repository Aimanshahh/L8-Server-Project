<?php

namespace Tobuli\Reports\Reports;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\History\Group;

use Tobuli\Reports\DeviceHistoryReport;

class GeofencesStopNewReport extends DeviceHistoryReport
{
    const TYPE_ID = 89;

    protected $disableFields = ['speed_limit'];
    protected $validation = ['geofences' => 'required'];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.geofence_stop_new');
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

    protected function getTable($data)
    {
        $rows = [];

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
        }

        return [
            'rows'   => $rows,
            'totals' => $this->getDataFromGroup($data['groups']->merge(), [
                'duration',
                'distance',
            ]),
        ];
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return [];
    }
}