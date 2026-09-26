<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DropDeviceFreeTablesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'positions:free_tables {action=view}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated: no per-device position tables exist in new Traccar schema.';

    /**
     * Execute the console command.
     *
     * NEW ARCHITECTURE:
     *   All positions live in a single tc_positions table. There are no
     *   positions_<device_id> tables to garbage-collect.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('positions:free_tables is disabled. New Traccar uses a single tc_positions table.');

        return 0;
    }
}