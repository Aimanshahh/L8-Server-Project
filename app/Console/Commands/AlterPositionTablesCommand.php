<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AlterPositionTablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'positions:tables_alter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated: per-device positions_ tables no longer exist in the new Traccar schema.';

    /**
     * Execute the console command.
     *
     * NEW ARCHITECTURE:
     *   New Traccar uses a single tc_positions table managed by Traccar itself.
     *   There are no per-device positions_<id> tables and no legacy columns
     *   like device_id / power to drop.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('positions:tables_alter is disabled. New Traccar schema uses a single tc_positions table.');

        return 0;
    }
}