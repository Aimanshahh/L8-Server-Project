<?php

namespace Tobuli\Helpers\Alerts\Check;

use Tobuli\Entities\Checklist;
use Tobuli\Entities\ChecklistTemplate;
use Tobuli\Entities\Event;

class IgnitionDurationAlertCheck extends AlertCheck
{
    public function checkEvents($position, $prevPosition)
    {
        if (! $this->check()) {
            return null;
        }

        $event = $this->getEvent();

        $event->type = Event::TYPE_IGNITION_DURATION;
        $event->message = '';
        $event->setAdditional('ignition_duration', round($this->device->getIgnitionDuration() / 60));

        $this->silent($event);

        return [$event];
    }

    public function check()
    {
        $position = $this->getPosition();

        if (! $position) {
            return false;
        }

        $alertDuration = $this->alert->ignition_duration * 60; //in seconds

        if ($alertDuration < 1) {
            return false;
        }

        $duration = $this->device->getIgnitionDuration();

        if ($duration < $alertDuration) {
            return false;
        }

        if ($this->device->isOffline()) {
            return false;
        }

        if (! $this->checkAlertPosition($position)) {
            return false;
        }

        if ($this->alert->pre_start_checklist_only) {
            if (! Checklist::whereIn('service_id', $this->device->services()->pluck('id'))
                ->incomplete()
                ->type(ChecklistTemplate::TYPE_PRE_START)
                ->count()
            ) {
                return false;
            }
        }

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