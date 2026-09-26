<?php namespace Tobuli\Helpers\Alerts\Check;

use App\Jobs\ConfirmFuelLevelChange;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Tobuli\Entities\Alert;
use Tobuli\Entities\Device;
use Tobuli\Entities\DeviceSensor;
use Tobuli\Entities\Event;
use Tobuli\Entities\TraccarPosition;

class FuelLevelChangeCheck extends AlertCheck
{
    const PERCENTAGE_CHANGE_THRESHOLD = 5;

    private $processManager;
    private ?Logger $logger = null; // tmp logger

    public function __construct(Device $device, Alert $alert)
    {
        parent::__construct($device, $alert);

        $this->processManager = ConfirmFuelLevelChange::generateProcessManager();

        if (config('tobuli.fuel_check_lock_log')) {
            $this->logger = (new Logger('fuel_check'))
                ->pushHandler(new StreamHandler(storage_path('logs/fuel_check_' . date('Y-m-d') . '.log')));
        }
    }

    /**
     * @param TraccarPosition $position
     * @param TraccarPosition $prevPosition
     * @return array|null
     */

    public function checkEvents($position, $prevPosition)
    {
        if ( ! $this->check($position)) {
            return null;
        }

        $sensors = $this->device->getSensorsByType(['fuel_tank']);

        $success = false;

        foreach ($sensors as $sensor) {
            $success = $success || ($this->checkSensorEvents($sensor, $position, $prevPosition) !== null);
        }

        return $success ? [] : null;
    }

    private function checkSensorEvents(DeviceSensor $sensor, $position, $prevPosition)
    {
        $processKey = "{$this->alert->id}_{$this->device->id}_{$sensor->id}";

        if ( ! $this->processManager->lock($processKey)) {
            if ($this->logger) {
                $this->logger->info($position, ['processKey' => $processKey, 'line' => __LINE__]);
            }

            return null;
        }

        $fail = function () use ($processKey) {
            $this->processManager->unlock($processKey);
            return null;
        };

        try {
            $change = getSensorFuelDifference($sensor, [$prevPosition, $position]);
        } catch (\Exception $exception) {
            return $fail();
        }

        $percent = $change['percent'];

        if (abs($percent) < self::PERCENTAGE_CHANGE_THRESHOLD)
            return $fail();

        switch ((int)$this->alert->state) {
            // only Fill
            case 1:
                if (!($percent > 0)) return $fail();
                break;
            // only Theft
            case 2:
                if ($percent > 0) return $fail();
                break;
        }

        $event = $this->getEvent();
        $event->type = $percent > 0 ? Event::TYPE_FUEL_FILL : Event::TYPE_FUEL_THEFT;
        $event->setAdditional('sensor_id', $sensor->id);

        dispatch(
            (new ConfirmFuelLevelChange($processKey, $position->time, $event->toArray(), $change))
                ->delay(ConfirmFuelLevelChange::SECONDS_GAP)
        );

        return [];
    }

    /**
     * @param TraccarPosition $position
     * @return bool|null
     */

    private function check($position)
    {
        if ( ! $position)
            return false;

        if ( ! $position->isValid())
            return null;

        if ( ! $this->isValidParkedDuration($position))
            return false;

        if ( ! $this->checkAlertPosition($position))
            return false;

        return true;
    }

    /**
     * New Traccar schema has no stop_begin_at / move_begin_at / parked_end_at.
     * We approximate parking state with tc_devices.motionstate / motiontime.
     */
    private function isValidParkedDuration($position): bool
    {
        if (!$this->device->fuel_detect_sec_after_stop) {
            return true;
        }

        if (!$this->device->traccar) {
            return true;
        }

        $positionTime = strtotime($position->time);
        $motionTime   = $this->device->traccar->motiontime ?? null;
        $motionState  = (int) ($this->device->traccar->motionstate ?? 0);

        if (!$motionTime) {
            return true;
        }

        // Motionstate = 1 → currently moving → not parked
        // Motionstate = 0 → currently stopped → parked since motiontime
        if ($motionState === 1) {
            // Not parked, but allow if recently stopped
            $stoppedAt = strtotime($motionTime);

            return ($positionTime - $stoppedAt) >= $this->device->fuel_detect_sec_after_stop;
        }

        // Currently stopped → check how long ago the stop began
        $stopDuration = $positionTime - strtotime($motionTime);

        return $stopDuration >= $this->device->fuel_detect_sec_after_stop;
    }
}