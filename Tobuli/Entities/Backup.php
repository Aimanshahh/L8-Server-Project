<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tobuli\Traits\Searchable;

class Backup extends AbstractEntity
{
    use Searchable;

    protected $fillable = [
        'name',
        'message',
        'launcher',
    ];

    protected array $searchable = [
        'name',
        'message',
        'launcher',
    ];

    public function processes(): HasMany
    {
        return $this->hasMany(BackupProcess::class, 'backup_id');
    }

    public function progressTotal(): int
    {
        return $this->processes()->sum('total');
    }

    public function progressDone(): int
    {
        return $this->processes()->sum('processed');
    }

    public function isCompleted(): bool
    {
        $processes = $this->processes;

        return $processes->count() === $processes->whereNotNull('completed_at')->count();
    }

    public function runnableProcesses(): \Generator
    {
        $now = date('Y-m-d H:i:s');
        $processes = $this->processes()->cursor();

        foreach ($processes as $process) {
            if ($process->isRunnable() === false) {
                continue;
            }

            $isUpdated = BackupProcess::where('id', $process->id)
                ->where(fn (Builder $query) => $query
                    ->where('reserved_at', '<', $now)
                    ->orWhereNull('reserved_at')
                )
                ->toBase()
                ->update(['reserved_at' => $now]);

            if ($isUpdated) {
                yield $process;
            }
        }
    }
}