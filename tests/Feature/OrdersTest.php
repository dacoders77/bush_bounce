<?php

namespace Tests\Feature;

use App\Http\Controllers\OrderController;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use \App\Order;
use Illuminate\Support\Facades\DB;

class OrdersTest extends TestCase
{
    //use RefreshDatabase;

    /* @see https://www.youtube.com/watch?v=WlrakUbyaHI */
    //use DatabaseMigrations;
    //use WithoutMiddleware;

    /**
     * A basic test example.
     *
     * @return void
     */
    //public function testExample()
    //{
        // Call addOpenOrder("long", 12.2)
        //$this->assertTrue();
    //}
    private $orderVolume;


    public function test_add_order_and_emprt_trade(){

        // Clear DB on each run. TESTING ONLY!
        DB::table("orders")->truncate();

        OrderController::addOpenOrder("long", 55, 212);

        // Add empty trade record
        $addEpmtyRecordID = OrderController::addEmptyTrade("short", 2, 231);
        $this->assertEquals(Order::where('id', $addEpmtyRecordID)->value('trade_direction'), 'short');
        $this->assertEquals(Order::where('id', $addEpmtyRecordID)->value('trade_volume'), 2);
        $this->assertEquals(Order::where('id', $addEpmtyRecordID)->value('out_price'), 231);
    }

    public function test_add_open_order_adds_record(){

        // Generate random array with fixed length
        $tradeVolumeArray = range(1, 10);
        shuffle($tradeVolumeArray );
        $tradeVolumeArray = array_slice($tradeVolumeArray ,0,10);
        $inPrice = rand(180, 290);

        // Generate array length
        $arraylength = rand(2, 5);

        // Run through cuted array
        for($i = 0; $i < $arraylength; $i++){
            //echo "el: " . $tradeVolumeArray[$i] . "\n";
            $this->orderVolume += $tradeVolumeArray[$i];
        }

        for ($i = 0; $i < 3; $i++ ){
            // Place IN order
            $result = OrderController::addOpenOrder("long", $this->orderVolume, $inPrice);
            $this->assertEquals(Order::where('id', $result)->value('order_direction'), "long");
            $this->assertEquals(Order::where('id', $result)->value('order_volume'), $this->orderVolume);
            $this->assertEquals(Order::where('id', $result)->value('in_price'), $inPrice);

            // Add empty trade record
            $addEpmtyRecordID = OrderController::addEmptyTrade("short", 1, 231);
            $this->assertEquals(Order::where('id', $addEpmtyRecordID)->value('trade_volume'), 1);

            for ($j = 0; $j < $arraylength; $j++){

                $outPrice = rand(180, 250); // Generate random price
                $rebatePerVolume = 0.01; // Fixed value. It comes from the exchange
                $lastRecord = Order::orderBy('id', 'desc'); // Get the last record

                $addedOrderRecordID = OrderController::addTrade("sell", $tradeVolumeArray[$j], $outPrice, $rebatePerVolume); // Trade out


                // Add to the table values
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('trade_direction'), 'sell');
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('trade_volume'), $tradeVolumeArray[$j]);
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('out_price'), $outPrice);
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('rebate_per_volume'), $rebatePerVolume * 2); // 0 - empty record volume



                // Calculate profit and other values in the row
                OrderController::calculateProfit($addedOrderRecordID);



                // Calculated values
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('order_volume'), Order::where('id', $addedOrderRecordID - 1)->value('order_volume') - $tradeVolumeArray[$j]);
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('profit_per_contract'), Order::where('id', $addedOrderRecordID)->value('out_price') - $inPrice);
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('profit_per_volume'), (Order::where('id', $addedOrderRecordID)->value('out_price') - $inPrice) * $tradeVolumeArray[$j]);
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('rebate_per_volume'), $rebatePerVolume * 2);
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('net_profit'), (Order::where('id', $addedOrderRecordID)->value('out_price') - $inPrice) * $tradeVolumeArray[$j] + $rebatePerVolume * 2);
                $this->assertEquals(Order::where('id', $addedOrderRecordID)->value('accum_profit'), Order::where('id', $addedOrderRecordID - 1)->value('accum_profit') + Order::where('id', $addedOrderRecordID)->value('net_profit'));

                
            }
        }
    }


}
