<?php

namespace Tobuli\Reports\Reports;

use Tobuli\History\Actions\EngineHours;
use Tobuli\History\Actions\Fuel;
use Tobuli\History\Group;
use Tobuli\History\Stats\StatSum;
use Tobuli\Reports\DeviceHistoryReport;

class FuelFlowRateReport extends DeviceHistoryReport
{
    const TYPE_ID = 83;

    public function typeID()
    {
        return self::TYPE_ID;
    }

    public function title()
    {
        return trans('front.fuel_flow_rate');
    }

    protected function getActionsList()
    {
        return [
            Fuel::class,
            EngineHours::class,
        ];
    }

    protected function precheckError($device)
    {
        if ($device->getSensorByType('fuel_consumption')) {
            return null;
        }

        return trans('global.dont_exist', [
            'attribute' => trans('front.sensor') . ' (' . trans('front.fuel_consumption') . ')'
        ]);
    }

    protected function generateDevice($device)
    {
        if ($error = $this->precheckError($device)) {
            return [
                'meta' => $this->getDeviceMeta($device),
                'error' => $error
            ];
        }

        $data = $this->getDeviceHistoryData($device);

        if ($this->isEmptyResult($data)) {
            return null;
        }

        /** @var Group $group */
        $group = $data['root'];
        $meta = $this->getDeviceMeta($device);

        $stats = $group->stats();

        $fuelConsumption = $stats->has('fuel_consumption') ? $stats->get('fuel_consumption')->value() : null;
        $engineSeconds = $stats->has('engine_hours') ? $stats->get('engine_hours')->value() : null;

        if ($fuelConsumption === null || $engineSeconds === null) {
            return null;
        }

        $group->stats()->set('fuel_consumption_custom', new StatSum());
        $group->stats()->set('engine_hours_custom', new StatSum());
        $group->stats()->set('flow_rate_m3_h', new StatSum());
        $group->stats()->set('flow_rate_l_s', new StatSum());

        if ($engineSeconds) {
            $group->stats()->apply('flow_rate_m3_h', $fuelConsumption / 1000 / $engineSeconds * 3600);
            $group->stats()->apply('flow_rate_l_s', $fuelConsumption / $engineSeconds);
            $group->stats()->apply('engine_hours_custom', $engineSeconds / 3600);
            $group->stats()->apply('fuel_consumption_custom', $fuelConsumption / 3600);
        }

        $item = $this->getDataFromGroup($group, [
            'fuel_consumption_custom',
            'engine_hours_custom',
            'location',
            'flow_rate_m3_h',
            'flow_rate_l_s'
        ]);

        $this->group->applyArray($group->stats()->all());

        $item['meta'] = $meta + $this->getHistoryMeta($group);

        return $item;
    }

    public static function isAvailable(): bool
    {
        return config('addon.report_fuel_tank_usage');
    }
}