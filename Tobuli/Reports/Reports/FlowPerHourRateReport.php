<?php

namespace Tobuli\Reports\Reports;

use Formatter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Reports\DeviceReport;

class FlowPerHourRateReport extends DeviceReport
{
    public const TYPE_ID = 84;

    private array $intervals = [];

    public function typeID(): int
    {
        return self::TYPE_ID;
    }

    public function title(): string
    {
        return trans('front.flow_per_hour_rate');
    }

    protected function beforeGenerate(): void
    {
        $period = new \DatePeriod(
            new \DateTime(date('Y-m-d H:i:s', strtotime(Formatter::time()->human($this->date_from)))),
            new \DateInterval('PT1H'),
            new \DateTime(date('Y-m-d H:i:s', strtotime(Formatter::time()->human($this->date_to)))),
        );

        $dateTo = $period->end;

        if ($dateTo->format('i') !== '00') {
            $period = iterator_to_array($period);
            $period[] = $dateTo;
        }

        foreach ($period as $value) {
            $datetime = $value->format('Y-m-d H:i:s');

            $date = date('Y-m-d', strtotime($datetime));
            $hour = date('H', strtotime($datetime));

            $this->intervals[$date][$hour] = [
                'net_amount'    => null,
                'flow_rate'     => null,
                'speed'         => null,
                'location'      => null,
            ];
        }
    }

    protected function generateDevice(Device $device): array
    {
        $rows = $this->intervals;

        try {
            $positions = $device->positions()
                ->whereBetween('time', [$this->date_from, $this->date_to])
                ->cursor();

            foreach ($positions as $position) {
                $flow = $this->getFlowData($position);

                if (empty($flow)) {
                    continue;
                }

                $date = date('Y-m-d', strtotime(Formatter::time()->human($position->time)));
                $hour = date('H', strtotime(Formatter::time()->human($position->time)));

                foreach ($flow as $key => $value) {
                    if ($rows[$date][$hour][$key] > $value) {
                        continue;
                    }

                    $rows[$date][$hour][$key] = $value;

                    if ($rows[$date][$hour]['location'] === null) {
                        $rows[$date][$hour]['location'] = $this->getLocation($position);
                    }
                }
            }
        } catch (QueryException $e) {}

        return [
            'meta'       => $this->getDeviceMeta($device),
            'table'      => $rows,
        ];
    }

    private function getFlowData(TraccarPosition $position): ?array
    {
        $params = $position->getParametersAttribute();

        if (!$params) {
            return null;
        }

        if (empty($params['result'])) {
            return null;
        }

        $result = $params['result'];

        $result = [
            'net_amount' => [$this->extractFlowParam($result, 'm3'), 1],
            'flow_rate' => [$this->extractFlowParam($result, 'm3/h'), 10],
            'speed' => [$this->extractFlowParam($result, 'm/s'), .1],
        ];

        $result = array_filter($result, fn ($value) => $value[0] !== null);
        $result = array_map(fn ($value) => round($value[0] * $value[1], 2), $result);

        return $result;
    }

    private function extractFlowParam(string $result, string $postfix): ?string
    {
        if (!Str::endsWith($result, $postfix)) {
            return null;
        }

        $result = explode('E', $result);

        return array_shift($result);
    }

    public static function isAvailable(): bool
    {
        return config('addon.report_fuel_tank_usage');
    }
}