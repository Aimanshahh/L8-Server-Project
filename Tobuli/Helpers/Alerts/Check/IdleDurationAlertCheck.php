<?php

namespace Tobuli\Helpers\Alerts\Check;

use Tobuli\Entities\Event;

class IdleDurationAlertCheck extends AlertCheck
{
    public function checkEvents($position, $prevPosition)
    {
        if ( ! $this->check())
            return null;

        $event = $this->getEvent();

        $event->type = Event::TYPE_IDLE_DURATION;
        $event->message = '';

        $event->setAdditional('idle_duration', round($this->device->getIdleDuration() / 60));

        $this->silent($event);

        return [$event];
    }

    public function check()
    {
        if ( $this->alert->idle_duration < 1 )
            return false;

        $duration = round($this->device->getIdleDuration() / 60);

        if ($duration < $this->alert->idle_duration )
            return false;

        if ($this->device->isOffline())
            return false;

        $position = $this->getPosition();

        if ( ! $position)
            return false;

        if ( ! $this->checkAlertPosition($position))
            return false;

        // New Traccar: engine_off_at does not exist.
        // Use motiontime (mapped to Device->moved_at) as approximation.
        $check_at = $this->device->moved_at;

        if ( ! $check_at)
            return false;

        if ( ! $this->checkOccurred($check_at))
            return false;

        return true;
    }
}