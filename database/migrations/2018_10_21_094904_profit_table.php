<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ProfitTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(env("PROFIT_TABLE"), function (Blueprint $table) {
            $table->increments('id');
            $table->string('symbol')->nullable();
            $table->string('order_id')->nullable();
            $table->decimal('volume', 8, 4)->nullable();

            $table->dateTime('order_in_placetime')->nullable();
            $table->dateTime('order_in_exectime')->nullable();
            $table->integer('order_in_duration')->nullable();
            $table->double('order_in_placedprice')->nullable();
            $table->double('order_in_execprice')->nullable();
            $table->double('order_in_pricediff')->nullable();

            $table->dateTime('order_out_placetime')->nullable();
            $table->dateTime('order_out_exectime')->nullable();
            $table->integer('order_out_duration')->nullable();
            $table->double('order_out_placedprice')->nullable();
            $table->double('order_out_execprice')->nullable();
            $table->double('order_out_execprice2')->nullable(); // Temp column. Each order move price stored here
            $table->double('order_out_pricediff')->nullable();

            $table->double('profit')->nullable();
            $table->double('rebate')->nullable();
            $table->double('net_profit')->nullable();
            $table->double('accumulated_profit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(env("PROFIT_TABLE_TABLE"));
    }
}
