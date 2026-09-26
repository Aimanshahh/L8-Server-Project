<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PositionsCsvImportCommand extends Command
{
    protected $signature = 'csv:positions';

    protected $description = 'Deprecated: per-device positions_ tables no longer exist.';

    /**
     * DEPRECATED.
     *
     * NEW ARCHITECTURE:
     *   Traccar owns tc_positions. Historical CSV position imports wrote rows
     *   into per-device positions_<id> tables. That schema no longer exists.
     *
     *   If historical positions must be imported, they have to be loaded into
     *   tc_positions with a proper deviceid mapping. That is a separate,
     *   explicitly reviewed operation — not a generic CSV command.
     *
     * @return void
     */
    public function handle(): void
    {
        $this->error('csv:positions is disabled.');
        $this->line('Traccar owns tc_positions. Per-device positions_ tables no longer exist.');
        $this->line('If you need to import historical positions, use a dedicated Traccar-aware importer.');
    }
}