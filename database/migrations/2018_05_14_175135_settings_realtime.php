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
            $table->string('app_mode');
            $table->boolean('broadcast_stop');
            $table->boolean('initial_start');
            $table->string('exchange');
            $table->string('symbol');
            $table->string('symbol_market');
            $table->string('price_step');
            $table->double('volume')->nullable();
            $table->integer('time_frame');
            $table->integer('request_bars');
            $table->integer('skip_ticks_msec');
            $table->integer('price_channel_period');
            $table->integer('sma_period')->nullable();
            $table->boolean('sma_filter_on')->nullable();
            $table->boolean('allow_trading');
            $table->string('trade_flag'); // Long or short trade flag. Indicates a position state
            $table->float('commission_value')->nullable();
            $table->date('history_from')->nullable();
            $table->date('history_to')->nullable();
        });

        DB::table('settings_realtime')->insert(array(
            'initial_start' => 1,
            'app_mode' => 'realtime',
            'broadcast_stop' => 0,
            'time_frame' => 1,
            'exchange' => 'hitbtc',
            'symbol' => "ETHUSD",
            'symbol_market' => "ETH/USDT",
            'price_step' => 0.01,
            'volume' => 0.1,
            'request_bars' => 30,
            'skip_ticks_msec' => 1000,
            'price_channel_period' => 5,
            'sma_period' => 2,
            'sma_filter_on' => 1,
            'allow_trading' => 0,
            'trade_flag' => 'all',
            'commission_value' => 0.2,
            'history_from' => '2018-06-01',
            'history_to' => '2018-06-02'
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
