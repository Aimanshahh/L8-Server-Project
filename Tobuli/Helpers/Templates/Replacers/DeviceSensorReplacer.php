<?php

namespace Tobuli\Helpers\Templates\Replacers;

use Illuminate\Database\Eloquent\Collection;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Sensors\SensorsManager;

class DeviceSensorReplacer extends Replacer
{
    /**
     * @param Device $device
     * @return array
     */
    public function replacers($device)
    {
        $list = array_flip($this->getSensorTypes());

        return $this->formatFields($device, $list);
    }

    /**
     * @return array
     */
    public function placeholders()
    {
        $sensors = [];

        foreach ($this->getSensorTypes() as $key => $name) {
            $sensors[$this->formatKey($key)] = $name;
        }

        return $sensors;
    }

    private function getSensorTypes(): array
    {
        $sensors = (new SensorsManager())->getEnabledListTitles();

        return array_merge(['all' => 'All sensor values'], $sensors);
    }

    protected function getAllSensorsValues(Device $device)
    {
        return $this->getFormattedSensorValues($device, $device->sensors);
    }

    protected function getSensorValue(Device $device, string $type)
    {
        $sensors = $device->getSensorsByType($type);

        if (!$sensors || $sensors->isEmpty()) {
            return dontExist('front.sensor');
        }

        return $this->getFormattedSensorValues($device, $sensors);
    }

    private function getFormattedSensorValues(Device $device, Collection $sensors): string
    {
        return $sensors->map(function ($sensor) use ($device) {
            return $sensor->name . ' - ' . $sensor->getValueCurrent($device)->getFormatted();
        })->implode(' | ');
    }

    protected function getFieldValue($model, $field)
    {
        if ($field === 'all') {
            return $this->getAllSensorsValues($model);
        }

        return $this->getSensorValue($model, $field);
    }
}