<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AlterSubscriptionsTableForeigns extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        // user_id → unsigned int (agar column hai)
        if (Schema::hasColumn('subscriptions', 'user_id')) {
            DB::statement('ALTER TABLE `subscriptions` MODIFY `user_id` INT(10) UNSIGNED NULL;');
        }

        // billing_plan_id → agar column hai to modify, warna skip
        if (Schema::hasColumn('subscriptions', 'billing_plan_id')) {
            DB::statement('ALTER TABLE `subscriptions` MODIFY `billing_plan_id` INT(10) UNSIGNED NULL;');
        }

        // Purane FKs drop karo (agar hain)
        try {
            Schema::table('subscriptions', function($table) {
                $table->dropForeign('subscriptions_user_id_foreign');
            });
        } catch (\Throwable $e) {}

        try {
            Schema::table('subscriptions', function($table) {
                $table->dropForeign('subscriptions_billing_plan_id_foreign');
            });
        } catch (\Throwable $e) {}

        // user_id FK add karo (agar users table hai)
        if (Schema::hasTable('users') && Schema::hasColumn('subscriptions', 'user_id')) {
            try {
                Schema::table('subscriptions', function($table) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                });
            } catch (\Throwable $e) {}
        }

        // billing_plan_id FK add karo (agar billing_plans table aur column dono hain)
        if (Schema::hasTable('billing_plans') && Schema::hasColumn('subscriptions', 'billing_plan_id')) {
            try {
                Schema::table('subscriptions', function($table) {
                    $table->foreign('billing_plan_id')->references('id')->on('billing_plans')->onDelete('set null');
                });
            } catch (\Throwable $e) {}
        }
    }

    public function down()
    {
        // no-op
    }
}
