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

            $table->integer('trade_id')->nullable();
            $table->string('client_order_id')->nullable();
            $table->string('order_id')->nullable();
            $table->string('symbol')->nullable();
            $table->string('side')->nullable();
            $table->string('quantity')->nullable();
            $table->string('price')->nullable();
            $table->string('fee')->nullable();
            $table->string('time')->nullable();
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
