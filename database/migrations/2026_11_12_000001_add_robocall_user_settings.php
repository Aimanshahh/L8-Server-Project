<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRobocallUserSettings extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('users', 'robo_phone_numbers')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('robo_phone_numbers')->nullable();
            });
        }

        if (!Schema::hasTable('robo_call_settings')) {
            Schema::create('robo_call_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->tinyInteger('enabled')->default(0);
                $table->unsignedSmallInteger('fallback_minutes')->default(5);
                $table->timestamps();
            });
        }

        if (!DB::table('robo_call_settings')->where('id', 1)->exists()) {
            DB::table('robo_call_settings')->insert([
                'id' => 1, 'enabled' => 0, 'fallback_minutes' => 5,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('robo_call_settings');

        if (Schema::hasColumn('users', 'robo_phone_numbers')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('robo_phone_numbers');
            });
        }
    }
}
