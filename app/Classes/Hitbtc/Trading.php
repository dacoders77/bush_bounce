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
    private $priceShift = 0; // How far the limit order will be placed away from the market price
    private $orderId;
    private $orderPlacePrice;
    private $activeOrder = null; // When there is an order present
    private $needToMoveOrder = true;
    private $rateLimitTime = 0; // Replace an order once a second
    private $rateLimitFlag = true; // Enter to the rate limit condition once

    public function __construct()
    {
    }

    public function parseTicker($bid = null, $ask = null){


        ($bid ? $direction = "buy" : $direction = "sell");

        if ($this->activeOrder == null){

            $this->orderId = floor(round(microtime(true) * 1000));
            ($direction == "buy" ? $this->orderPlacePrice = $bid - $this->priceStep * $this->priceShift : $this->orderPlacePrice = $ask + $this->priceStep * $this->priceShift);

            Cache::put('orderObject', new OrderObject(false, $direction, $this->orderPlacePrice , $this->orderId, ""), 5);
            $this->activeOrder = "placed";

        }

        // When order placed, start to move if needed
        if ($this->activeOrder == "new"){

            ($direction == "buy" ? $priceToCheck = $bid - $this->priceStep * $this->priceShift : $priceToCheck = $ask + $this->priceStep * $this->priceShift);
            if ($this->orderPlacePrice != $priceToCheck){

                if ($this->needToMoveOrder){
                    echo "NEED to move the order! \n";

                    if (time() > $this->rateLimitTime || $this->rateLimitFlag){
                        ($direction == "buy" ? $this->orderPlacePrice = $bid - $this->priceStep * $this->priceShift : $this->orderPlacePrice = $ask + $this->priceStep * $this->priceShift);
                        $tempOrderId = (string)microtime();
                        Cache::put('orderObject', new OrderObject(true,"", $this->orderPlacePrice, $this->orderId, $tempOrderId), 5);
                        $this->orderId = $tempOrderId;
                        $this->needToMoveOrder = false;

                        $this->rateLimitFlag = false;
                        $this->rateLimitTime = time() + 2;
                    }
                    else{
                        dump('rate limit');
                    }
                }
            }
        }
    }

    public function parseActiveOrders(array $message){
        // When order placed
        if ($message['params']['clientOrderId'] == $this->orderId && $message['params']['status'] == "new"){
            //die('placed');
            $this->activeOrder = "new";
        }

        // When order filled
        if ($message['params']['clientOrderId'] == $this->orderId && $message['params']['status'] == "filled"){
            $this->activeOrder = "filled"; // Then we can open a new order
            //die('filled');
            dump('filled');
            Cache::put('commandExit', true, 5);
        }
    }

    public function parseOrderMove(array $message){
        dump ("---Order moved CONFIRM!");
        $this->needToMoveOrder = true;
    }
}

