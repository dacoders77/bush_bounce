<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('order_direction')->nullable();
            $table->string('trade_direction')->nullable();
            $table->decimal('order_volume', 6, 3)->nullable();
            $table->decimal('trade_volume', 6, 3)->nullable();
            $table->decimal('in_price', 8, 3)->nullable();
            $table->decimal('out_price', 8, 3)->nullable();
            $table->decimal('profit_per_contract', 6, 3)->nullable();
            $table->decimal('profit_per_volume', 6, 3)->nullable();
            $table->decimal('rebate_per_volume', 8, 5)->nullable();
            $table->decimal('net_profit', 8, 5)->nullable();
            $table->decimal('accum_profit', 10, 5)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
