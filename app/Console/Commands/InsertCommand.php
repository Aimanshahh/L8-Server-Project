<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class InsertCommand extends Command
{
    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'insert:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated: Traccar now writes positions directly to tc_positions.';

    /**
     * Execute the console command.
     *
     * NEW ARCHITECTURE:
     *   Traccar receives GPS packets and writes them to tc_positions.
     *   Laravel no longer receives or writes raw GPS positions.
     *
     *   This command is kept as a safe no-op so existing cron / supervisor
     *   definitions do not break. It must NOT:
     *     - read from Redis PositionsStack
     *     - call PositionsWriter
     *     - create positions_<device_id> tables
     *     - write duplicate positions
     *
     * @return int
     */
    public function handle()
    {
        $this->debug = ! empty($this->argument('debug'));

        if ($this->debug) {
            $this->line('insert:run is disabled. Traccar handles GPS ingestion.');
        }

        return 0;
    }

    protected function getArguments()
    {
        return array(
            array('debug', InputArgument::OPTIONAL, 'Debug')
        );
    }
}