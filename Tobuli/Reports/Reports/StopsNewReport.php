<?php namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\GroupStop;
use Tobuli\History\Actions\Distance;
use Tobuli\History\Actions\Drivers;
use Tobuli\History\Actions\DriveStop;
use Tobuli\History\Actions\Duration;
use Tobuli\History\Actions\EngineHours;
use Tobuli\History\Actions\Fuel;
use Tobuli\History\Actions\GeofencesIn;
use Tobuli\History\Actions\OdometersDiff;
use Tobuli\History\Actions\Speed;

class StopsNewReport extends DrivesStopsGeofencesReport
{
    const TYPE_ID = 94;
    protected $validation = ['geofences' => 'required'];

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.stops_new');
    }

    protected function getActionsList()
    {
        $list = [
            DriveStop::class,
            Duration::class,
            Distance::class,
            Speed::class,
            Fuel::class,
            EngineHours::class,
            Drivers::class,
            OdometersDiff::class,

            GroupStop::class,
            GeofencesIn::class
        ];

       return $list;
    }
}