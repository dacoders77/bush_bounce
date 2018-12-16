<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFactOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fact_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->timestamps();
            $table->string('time')->nullable();

            $table->integer('trade_id')->nullable();
            $table->string('client_order_id')->nullable();
            $table->string('order_id')->nullable();
            $table->string('symbol')->nullable();
            $table->string('side')->nullable();
            $table->string('quantity')->nullable();
            $table->string('price')->nullable();
            $table->string('fee')->nullable();
            $table->decimal('profit', 10, 5)->nullable();
            $table->decimal('net_profit', 10, 5)->nullable();
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
        Schema::dropIfExists('fact_orders');
    }
}
