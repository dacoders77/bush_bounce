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
            $table->boolean('broadcast_stop');
            $table->boolean('initial_start');
            $table->string('symbol');
            $table->integer('time_frame');
            $table->integer('request_bars');
            $table->integer('price_channel_period');
            $table->boolean('allow_trading');
            $table->float('commission_value')->nullable();
            $table->date('history_from')->nullable();
            $table->date('history_to')->nullable();
        });

        DB::table('settings_realtime')->insert(array(
            'initial_start' => 1,
            'broadcast_stop' => 1,
            'time_frame' => 1,
            'symbol' => "BTCUSD",
            'request_bars' => 30,
            'price_channel_period' => 10,
            'allow_trading' => 0,
            'commission_value' => 0.2,
            'history_from' => '2018-06-01',
            'history_to' => '2018-06-05'
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
