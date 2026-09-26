<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\GroupDailySplit;
use Tobuli\History\Actions\GroupGeofenceIn;
use Tobuli\History\Group;
use Tobuli\Reports\DeviceHistoryReport;

use Tobuli\Entities\Event;
use Tobuli\Reports\DeviceReport;

class GeofencesNewReport extends DeviceHistoryReport
{
    const TYPE_ID = 86;

    protected $disableFields = ['speed_limit', 'stops'];

   
  

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.geofence_events');
    }
    protected function getActionsList()
    {
        return [
            DriveStop::class,

            GroupDailySplit::class,
            GroupGeofenceIn::class,
        ];
    }
    protected function getTable($data)
    {
        $rows = [];

        foreach ($data['groups']->all() as $group)
        {
            $rows[] = $this->getDataFromGroup($group, [
                'timestamp',
                'start_date_at',
                'start_time_at',
                'end_date_at',
                'end_time_at',
                'stop_duration',
                'drive_distance',
                'location',
                'group_geofence'
            ]);
        }

        return [
            'rows'   => $rows,
            'totals' => [],
        ];
    }

    protected function getTotals(Group $group, array $only = [])
    {
        return [];
    }
    protected function beforeGenerate()
    {
        parent::beforeGenerate();

        if (!$this->getSkipBlankResults())
            return;


        $query = $this->getDevicesQuery()->whereHas('events', function($q){
            $q->whereBetween('time', [$this->date_from, $this->date_to]);

            $subusers = $this->parameters['subusers'] ?? false;

            if ($subusers && $this->user->isManager()) {
                $q->whereIn('user_id', function ($q) {
                    $q->select('users.id')
                        ->from('users')
                        ->where('users.id', $this->user->id)
                        ->orWhere('users.manager_id', $this->user->id);
                });
            } else {
                $q->where('user_id', $this->user->id);
            }

            if ($types = array_get($this->parameters, 'custom'))
                $q->whereIn('type', $types);
        });

        $this->setDevicesQuery($query);

       $this->getDevicesQuery()->chunk(1000, function ($devices) {
            foreach ($devices as $device) {
                $data = $this->generateDevices($device);

                if ($this->getSkipBlankResults() && empty($data))
                    continue;

                if (empty($data)) {
                    $this->items[] = [
                        'meta' => $this->getDeviceMeta($device),
                        'error' => trans('front.nothing_found_request')
                    ];

                    continue;
                }

                foreach ($data['table']['rows'] as $row) {
                    $this->items[] = [
                        'meta' => $this->getDeviceMeta($device),
                        'table' => [
                            'rows' => [$row]
                        ]
                    ];
                }
            }
        });
       
       
    }

    protected function generateDevice($device)
    {
        $query = Event::with(['geofence'])
            ->whereBetween('time', [$this->date_from, $this->date_to])
            ->where('device_id', $device->id)
            ->where(function($query){
                $subusers = $this->parameters['subusers'] ?? false;

                if ($subusers && $this->user->isManager()) {
                    $query->whereIn('user_id', function ($q) {
                        $q->select('users.id')
                            ->from('users')
                            ->where('users.id', $this->user->id)
                            ->orWhere('users.manager_id', $this->user->id);
                    });
                } else {
                    $query->where('user_id', $this->user->id);
                }
            })
            ->orderBy('time', 'asc');

        if ($types = array_get($this->parameters, 'custom'))
            $query->whereIn('type', $types);

        $events = $query->get();

        if ($events->isEmpty())
            return null;

        $totals = [];

        foreach ($events as & $event) {
            $event['time']     = Formatter::time()->human($event['time']);
            $event['location'] = $this->getLocation((object)[
                'latitude' => $event['latitude'],
                'longitude' => $event['longitude']
            ]);
            $event['driver'] = array_get($event, 'additional.driver_name');

            if (empty($totals[$event['message']]))
                $totals[$event['message']] = [
                    'title' => trans('front.total') . ' ' . $event['message'],
                    'value' => 0,
                ];

            if (empty($this->totals[$event['message']]))
                $this->totals[$event['message']] = 0;

            $this->totals[$event['message']]++;
        }

       

    $data = $this->getDeviceHistoryData($device);
    

   
       
    
    foreach ($data['groups']->all() as $group)
    {
        $rows1[] = $this->getDataFromGroup($group, [
            'timestamp',
            'start_date_at',
            'start_time_at',
            'end_date_at',
            'end_time_at',
            'stop_duration',
            'drive_distance',
            'location',
            'group_geofence'
        ]);
    }
    

        return [
            'meta' => $this->getDeviceMeta($device),
            'table' => [
                'rows' => $events
            ],
            'table1' => [
                'rows1'   => $rows1,
            ],
            
           
            
        ];
       
    }
    
    protected function afterGenerate()
    {
        if (empty($this->items))
            return;

        $this->items = array_sort($this->items, function($item) {
            return $item['table1']['rows1'][0]['timestamp'] ?? null;
        });
    }

    protected function toCSVData($file)
    {
        foreach ($this->getItems() as $item) {
            $metas = array_pluck($item['meta'], 'value');

            if (empty($item['table']['rows']))
                continue;

            foreach ($item['table']['rows'] as $row) {
                $values = $metas;
                $values[] = $row['time'];
                $values[] = $row['message'];
                $values[] = strip_tags($row['location']);

                fputcsv($file, $values);
            }
        }
    }
}