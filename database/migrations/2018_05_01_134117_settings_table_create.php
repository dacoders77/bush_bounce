<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SettingsTableCreate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('default_time_frame');
            $table->integer('default_price_channel_period');
            $table->integer('default_stop_loss_shift');
            $table->float('commission_value')->nullable();
        });

        DB::table('settings')->insert(array(
            'default_time_frame' => "15m",
            'default_price_channel_period' => 10,
            'default_stop_loss_shift' => 10,
            'commission_value' => 0.2,
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }
}
