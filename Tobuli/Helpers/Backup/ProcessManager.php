<?php

namespace Tobuli\Helpers\Backup;

use Tobuli\Entities\Backup;
use Tobuli\Entities\BackupProcess;
use Tobuli\Helpers\Backup\Process\AbstractBackuper;
use Tobuli\Helpers\Backup\Uploader\BackupFtp;

class ProcessManager
{
    private BackupFtp $ftp;
    private Backup $backup;

    public function __construct(BackupFtp $ftp)
    {
        $this->ftp = $ftp;
    }

    public function handle(): void
    {
        $processes = $this->backup->runnableProcesses();

        foreach ($processes as $process) {
            try {
                $this->resolveBackuper($process, $this->ftp)->run();
            } catch (\Throwable $e) {
                $this->backup->update(['message' => $e->getMessage()]);

                throw $e;
            }
        }

        if ($this->backup->isCompleted()) {
            $this->backup->update(['message' => trans('front.successfully_uploaded')]);
        }
    }

    public static function resolveBackuper(BackupProcess $process, BackupFtp $ftp): AbstractBackuper
    {
        return new $process->type($process, $ftp);
    }

    public function setBackup(Backup $backup): self
    {
        $this->backup = $backup;

        return $this;
    }
}