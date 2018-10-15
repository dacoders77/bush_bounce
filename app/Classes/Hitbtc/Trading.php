<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 10/14/2018
 * Time: 6:12 AM
 */

namespace App\Classes\Hitbtc;

use Illuminate\Support\Facades\Cache;


class Trading
{
    private $priceStep = 0.000001; // ETHBTC price step
    private $priceShift = 10;
    private $orderId;
    private $orderPlacePrice;
    private $activeOrder = null; // When there is an order present
    private $needToMoveOrder = true;

    public function __construct()
    {
    }

    public function parseTicker(array $message){
        //echo $message['params']['bid'] . "\n";

        // 1. Get Bid +
        // 2. Calculate the price: -5 price steps
        // 3. Place order with: price, direction and oredrId
        // 4. Check whether it is on the same place:
        // - yes: do nothing
        // - no: amend it

        // Place order: direction, price, orderId

        echo $message['params']['bid'] . "\n";

        if ($this->activeOrder == null){

            $this->orderId = (string)time();
            $this->orderPlacePrice = $message['params']['bid'] - $this->priceStep * $this->priceShift;
            Cache::put('orderObject', new OrderObject(false,"buy", $this->orderPlacePrice , $this->orderId, ""), 5);
            $this->activeOrder = "placed";
        }

        // When order placed, start to move if needed
        if ($this->activeOrder == "new"){

            // If the price has moved and the order needs to be moved to a new price
            //$z = $message['params']['bid'] - $this->priceStep * $this->priceShift;
            //echo $this->orderPlacePrice . " " . $z . "\n";

            if ($this->orderPlacePrice != $message['params']['bid'] - $this->priceStep * $this->priceShift){

                if ($this->needToMoveOrder){
                    echo "NEED to move the order! \n";
                    $this->orderPlacePrice = $message['params']['bid'] - $this->priceStep * $this->priceShift;

                    $tempOrderId = (string)time();
                    Cache::put('orderObject', new OrderObject(true,"", $this->orderPlacePrice, $this->orderId, $tempOrderId), 5);
                    $this->orderId = $tempOrderId;

                    $this->needToMoveOrder = false;

                }

            }
        }
    }

    public function parseActiveOrders(array $message){
        //echo "Parse active orders! \n";

        //die($message['params']['status']);
        // When order placed
        if ($message['params']['clientOrderId'] == $this->orderId && $message['params']['status'] == "new"){

            //die('placed');
            $this->activeOrder = "new";
        }

        // When order filled
        if ($message['params']['clientOrderId'] == $this->orderId && $message['params']['status'] == "filled"){
            $this->activeOrder = "filled"; // Then we can open a new order
            die('filled');
        }
    }

    public function parseOrderMove(array $message){
        //die('moved');
        echo "Order moved xx \n";
        $this->needToMoveOrder = true;
    }
}

