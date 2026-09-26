<?php

namespace App\Jobs;

use App\Events\NoticeEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Entities\User;

class DevicePositionsImportJob implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    public TraccarDevice $device;
    public string $file;
    public ?User $actor;

    public function __construct(TraccarDevice $device, string $file, ?User $actor)
    {
        $this->device = $device;
        $this->file = $file;
        $this->actor = $actor;
    }

    public function handle(): void
    {
        if (File::missing($this->file)) {
            return;
        }

        $process = Process::fromShellCommandline($this->getCommand());
        $process->run();

        while ($process->isRunning());

        $this->sendNotice($process->isSuccessful());

        File::delete($this->file);
    }

    private function sendNotice(bool $success): void
    {
        $event = $success
            ? new NoticeEvent($this->actor, NoticeEvent::TYPE_SUCCESS, trans('front.successfully_uploaded'))
            : new NoticeEvent($this->actor, NoticeEvent::TYPE_ERROR, trans('front.upload_failed'));

        event($event);
    }

    private function getCommand(): string
    {
        $config = $this->device->positions()->getConnection()->getConfig();

        $command = 'mysql'
            . " -u {$config['username']}"
            . " -p{$config['password']}"
            . " {$config['database']}";

        if (str_ends_with($this->file, '.gz')) {
            $command = "zcat $this->file | $command";
        } else {
            $command .= " < $this->file";
        }

        return $command;
    }
}
