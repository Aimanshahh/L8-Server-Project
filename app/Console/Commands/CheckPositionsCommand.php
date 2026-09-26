<?php namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckPositionsCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'positions:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deprecated: Redis position stack is no longer used.';

    /**
     * DEPRECATED.
     *
     * NEW ARCHITECTURE:
     *   Traccar receives GPS packets and writes them to tc_positions.
     *   Laravel's Redis position stack is unused, so there is nothing to check.
     *
     * @return int
     */
    public function handle()
    {
        $this->line('positions:check is disabled.');
        $this->line('Traccar handles GPS ingestion; the Redis position stack is unused.');

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