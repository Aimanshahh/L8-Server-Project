<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

class CalcVirtualOdometerCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'virtual_odometer:calc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated: Traccar manages tc_positions. Laravel must not write position distance.';

    /**
     * Execute the console command.
     *
     * WHY DISABLED:
     *   Old implementation wrote to positions.distance and updated
     *   per-device odometer sensor counters by re-reading positions.
     *   In the new Traccar schema:
     *     - tc_positions has no "distance" column (distance lives in attributes JSON).
     *     - tc_positions is owned by Traccar; writing to it can corrupt data.
     *     - Traccar itself computes distance and stores it in attributes.
     *
     *   If a virtual-odometer feature is required in the future, it must be
     *   re-implemented as a read-only aggregation on top of tc_positions.
     *
     * @return int
     */
    public function handle()
    {
        return 0;
    }

    protected function getArguments()
    {
        return array();
    }

    protected function getOptions()
    {
        return array();
    }
}