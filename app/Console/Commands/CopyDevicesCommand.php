<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

class CopyDevicesCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'positions:copy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated: multi-database position architecture no longer supported.';

    /**
     * Execute the console command.
     *
     * NEW ARCHITECTURE:
     *   New Traccar stores all positions in a single tc_positions table on the
     *   traccar_mysql connection. There is no database_id concept anymore and
     *   no per-device position tables to move between databases.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('positions:copy is disabled. New Traccar uses a single database / single tc_positions table.');

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