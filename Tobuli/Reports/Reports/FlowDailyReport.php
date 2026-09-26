<?php

namespace Tobuli\Reports\Reports;

use Carbon\Carbon;
use Formatter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarPosition;
use Tobuli\Reports\DeviceReport;

class FlowDailyReport extends DeviceReport
{
    public const TYPE_ID = 85;

    public array $intervals = [];
    private float $lastEngineHours;

    public function typeID(): int
    {
        return self::TYPE_ID;
    }

    public function title(): string
    {
        return trans('front.flow_daily');
    }

    protected function beforeGenerate(): void
    {
        $period = new \DatePeriod(
            new \DateTime(date('Y-m-d H:i:s', strtotime(Formatter::time()->human($this->date_from)))),
            new \DateInterval('P1D'),
            new \DateTime(date('Y-m-d H:i:s', strtotime(Formatter::time()->human($this->date_to)))),
        );

        $dateTo = $period->end;

        if (!Carbon::parse($dateTo)->isMidnight()) {
            $period = iterator_to_array($period);
            $period[] = $dateTo;
        }

        foreach ($period as $value) {
            $date = $value->format('Y-m-d');

            $this->intervals[$date] = [
                'net_amount_min'    => null,
                'net_amount_max'    => null,
                'net_amount_diff'   => null,
                'engine_hours_from' => null,
                'engine_hours_to'   => null,
                'engine_hours_diff' => null,
                'rate_m3_h'         => null,
                'rate_l_s'          => null,
                'location'          => null,
            ];
        }
    }

    protected function afterGenerate()
    {
        $this->totals = [
            'net_amount_diff'   => null,
            'engine_hours_diff' => null,
            'rate_m3_h'         => null,
            'rate_l_s'          => null,
        ];

        foreach ($this->items as &$item) {
            $prevDate = null;

            foreach ($item['table'] as $date => &$row) {
                if ($prevDate) {
                    $row['net_amount_min'] = $item['table'][$prevDate]['net_amount_max'];
                }

                if (isset($row['net_amount_min']) && isset($row['net_amount_max'])) {
                    $row['net_amount_diff'] = $row['net_amount_max'] - $row['net_amount_min'];
                    $this->totals['net_amount_diff'] += $row['net_amount_diff'];
                }

                if (isset($row['engine_hours_from']) && isset($row['engine_hours_to'])) {
                    $row['engine_hours_diff'] = $row['engine_hours_to'] - $row['engine_hours_from'];
                    $this->totals['engine_hours_diff'] += $row['engine_hours_diff'];
                }

                $prevDate = $date;
            }
        }

        if ($this->totals['net_amount_diff'] !== null && $this->totals['engine_hours_diff'] !== null) {
            $this->totals['rate_m3_h'] = $this->totals['engine_hours_diff']
                ? round($this->totals['net_amount_diff'] / $this->totals['engine_hours_diff'], 2)
                : 0;
            $this->totals['rate_l_s'] = round($this->totals['rate_m3_h'] * 1000 / 3600, 2);
        }
    }

    protected function generateDevice(Device $device): array
    {
        $this->lastEngineHours = 0;
        $rows = $this->intervals;

        try {
            $engineHoursAttr = $this->getDeviceEngineHoursAttribute($device);

            $positions = $device->positions()
                ->whereBetween('time', [$this->date_from, $this->date_to])
                ->cursor();

            foreach ($positions as $position) {
                $date = date('Y-m-d', strtotime(Formatter::time()->human($position->time)));

                $netAmount = $this->getNetAmount($position);
                $engineHours = $this->getEngineHours($position, $engineHoursAttr);

                if (!isset($rows[$date]['engine_hours_from'])) {
                    $rows[$date]['engine_hours_from'] = $engineHours;
                }

                $rows[$date]['engine_hours_to'] = $engineHours;
                $rows[$date]['engine_hours_diff'] = round($rows[$date]['engine_hours_to'] - $rows[$date]['engine_hours_from'], 2);

                if ($netAmount === null) {
                    continue;
                }

                if ($rows[$date]['net_amount_min'] > $netAmount || $rows[$date]['net_amount_min'] === null) {
                    $rows[$date]['net_amount_min'] = $netAmount;
                }

                if ($rows[$date]['net_amount_max'] < $netAmount || $rows[$date]['net_amount_max'] === null) {
                    $rows[$date]['net_amount_max'] = $netAmount;
                }

                if ($rows[$date]['location'] === null) {
                    $rows[$date]['location'] = $this->getLocation($position);
                }
            }

            foreach ($rows as &$row) {
                if (isset($row['net_amount_min']) && isset($row['net_amount_max'])) {
                    $row['net_amount_diff'] = $row['net_amount_max'] - $row['net_amount_min'];
                }

                if ($row['net_amount_diff'] !== null && $row['engine_hours_diff'] !== null) {
                    $row['rate_m3_h'] = $row['engine_hours_diff'] ? $row['net_amount_diff'] / $row['engine_hours_diff'] : 0;
                    $row['rate_l_s'] = $row['rate_m3_h'] * 1000 / 3600;

                    $row['rate_m3_h'] = round($row['rate_m3_h'], 2);
                    $row['rate_l_s'] = round($row['rate_l_s'], 2);
                }
            }
        } catch (QueryException $e) {}

        return [
            'meta'       => $this->getDeviceMeta($device),
            'table'      => $rows,
        ];
    }

    private function getEngineHours(TraccarPosition $position, string $attr): float
    {
        $value = $attr === TraccarPosition::ENGINE_HOURS_KEY
            ? $position->getEngineHours()
            : $position->getVirtualEngineHours();

        if (empty($value)) {
            return $this->lastEngineHours;
        }

        $value = round($value / 3600, 2);

        return $this->lastEngineHours = $value;
    }

    private function getNetAmount(TraccarPosition $position): ?float
    {
        $params = $position->getParametersAttribute();

        if (!$params) {
            return null;
        }

        if (empty($params['result'])) {
            return null;
        }

        $result = $this->extractFlowParam($params['result'], 'm3');

        if ($result === null) {
            return null;
        }

        return round($result, 2);
    }

    private function extractFlowParam(string $result, string $postfix): ?string
    {
        if (!Str::endsWith($result, $postfix)) {
            return null;
        }

        $result = explode('E', $result);

        return array_shift($result);
    }

    private function getDeviceEngineHoursAttribute(Device $device): string
    {
        $positions = $device->positions()
            ->orderliness('asc')
            ->whereBetween('time', [$this->date_from, $this->date_to])
            ->limit(1000)
            ->cursor();

        foreach ($positions as $position) {
            if (!$position->hasParameter(TraccarPosition::ENGINE_HOURS_KEY)) {
                continue;
            }

            return TraccarPosition::ENGINE_HOURS_KEY;
        }

        return TraccarPosition::VIRTUAL_ENGINE_HOURS_KEY;
    }

    public static function isAvailable(): bool
    {
        return config('addon.report_fuel_tank_usage');
    }
}