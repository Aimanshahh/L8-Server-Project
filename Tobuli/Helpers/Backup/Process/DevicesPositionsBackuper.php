<?php

namespace Tobuli\Helpers\Backup\Process;

use Tobuli\Entities\BackupProcess;

/**
 * DEPRECATED positions backup.
 *
 * NEW ARCHITECTURE:
 *   Old backup dumped per-device positions_<id> tables. New Traccar stores
 *   all positions in a single tc_positions table that is managed by Traccar.
 *
 *   This class now returns an empty item list so the backup feature safely
 *   does nothing instead of dumping the entire tc_positions table for each
 *   device.
 *
 *   To back up the full tc_positions table, use a standard MySQL dump.
 */
class DevicesPositionsBackuper extends AbstractBackuper
{
    /**
     * @param  mixed  $item
     */
    protected function backup($item): bool
    {
        return false;
    }

    protected function getProcessedItemId($item)
    {
        return $item->id ?? 0;
    }

    protected function getItems(): iterable
    {
        return [];
    }

    public static function makeProcess(string $source, array $options = []): BackupProcess
    {
        return new BackupProcess([
            'type'              => static::class,
            'source'            => $source,
            'options'           => $options,
            'duration_active'   => 30 * 60,
            'last_item_id'      => 0,
            'total'             => 0,
        ]);
    }
}