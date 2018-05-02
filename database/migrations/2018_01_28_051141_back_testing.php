<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BackTesting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('date')->nullable(); // Use nullable if this field can be blank
            $table->string('asset_name')->nullable();
            $table->boolean('show_on_startup')->nullable();
            $table->boolean('selected')->nullable();
            $table->boolean('backtest')->nullable();
            $table->string('timeframe')->nullable();
            $table->dateTime('load_history_start')->nullable();
            $table->dateTime('load_history_end')->nullable();
            $table->integer('price_channel_default_value')->nullable();
            $table->integer('stop_loss_shift')->nullable();
            $table->boolean('reinvest')->nullable();
            $table->float('initial_capital')->nullable();
            $table->float('ending_capital')->nullable();
            $table->float('net_profit')->nullable();
            $table->float('net_profit_prcnt')->nullable();
            $table->float('drawdown')->nullable();
            $table->float('drawdown_prcnt')->nullable();
            $table->integer('trades_quantity')->nullable();
            $table->integer('profit_trades')->nullable();
            $table->integer('los_quantity')->nullable();
            $table->float('commission_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assets');
    }
}
