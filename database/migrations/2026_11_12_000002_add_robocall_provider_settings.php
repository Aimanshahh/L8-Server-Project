<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddRobocallProviderSettings extends Migration
{
    protected $columns = [
        'api_key'                 => 'TEXT NULL',
        'amount'                  => 'INT UNSIGNED NOT NULL DEFAULT 1000',
        'cooldown_hours'          => 'SMALLINT UNSIGNED NOT NULL DEFAULT 8',
        'geofence_cooldown_hours' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 24',
        'answered_statuses'       => "VARCHAR(255) NOT NULL DEFAULT 'completed,call_completed,answered'",
        'not_reached_statuses'    => "VARCHAR(255) NOT NULL DEFAULT 'no_answer,busy,failed,congestion'",
    ];

    protected function has($name)
    {
        $r = DB::selectOne(
            "SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'hypegps_web' AND TABLE_NAME = 'robo_call_settings' AND COLUMN_NAME = ?",
            [$name]
        );

        return (int) $r->c > 0;
    }

    public function up()
    {
        foreach ($this->columns as $name => $definition) {
            if (!$this->has($name)) {
                DB::statement("ALTER TABLE hypegps_web.robo_call_settings ADD COLUMN `$name` $definition");
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->columns) as $name) {
            if ($this->has($name)) {
                DB::statement("ALTER TABLE hypegps_web.robo_call_settings DROP COLUMN `$name`");
            }
        }
    }
}
