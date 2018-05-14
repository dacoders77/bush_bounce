<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SettingsRealtime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings_realtime', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('initial_start');
            $table->string('symbol');
            $table->integer('time_frame');
            $table->integer('request_bars');
            $table->integer('price_channel_period');
            $table->boolean('allow_trading');
            $table->float('commission_value')->nullable();
        });

        DB::table('settings_realtime')->insert(array(
            'initial_start' => 1,
            'time_frame' => 5,
            'symbol' => "BTCUSD",
            'request_bars' => 30,
            'price_channel_period' => 1,
            'allow_trading' => 0,
            'commission_value' => 0.2
        ));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings_realtime');
    }
}
