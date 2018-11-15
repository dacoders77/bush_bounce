<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 10/14/2018
 * Time: 6:12 AM
 */

namespace App\Classes\Hitbtc;

use App\Classes\LogToFile;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * Basic trading class.
 * Places and moves orders.
 * All events like: order place confirmation, order move, confirmation etc. Are received in ccxtsocket.php
 * them methods of this class are called.
 *
 * In order to move an order thre conditions must be met:
 * 1. Placed order at the best bid/ask price is not the best anymore
 * 2. $this->needToMoveOrder = true;
 * 2. Rate limit. Order can be moved not requentley than certain (adjustable amount of time)
 *
 */
class Trading
{
    private $priceStep ; // ETHBTC price step 0.000001. ETHUSD 0.01
    private $priceShift = 0; // How far (steps) the limit order will be placed away from the market price.
    private $orderId;
    private $orderPlacePrice;
    private $orderQuantity;
    private $tradesArray = array(); // Trades array. Used for accumulated volume and order average fill price
    private $activeOrder = null; // When there is an order present
    private $needToMoveOrder = true;
    private $rateLimitTime = 0; // Replace an order once a second
    private $rateLimitFlag = true; // Enter to the rate limit condition once
    private $runOnceFlag = true; // Enter to the order placed IF only once

    private $averageOrderFillPrice;
    private $accumulatedOrderVolume;


    public function __construct()
    {
        $this->priceStep = DB::table('settings_realtime')->first()->price_step;
        $this->orderQuantity = DB::table('settings_realtime')->first()->volume;
    }

    /**
     * On each update bid/ask event this method is called.
     * Both parameters are optional. If (bid, null) - buy limit order will be placed.
     * @param   double @bid
     * @param   double @ask
     * @return  void
     */
    public function parseTicker($bid = null, $ask = null){

        /* Place order */
        ($bid ? $direction = "buy" : $direction = "sell");
        if ($this->activeOrder == null){

            $this->orderId = floor(round(microtime(true) * 1000));
            ($direction == "buy" ? $this->orderPlacePrice = $bid - $this->priceStep * $this->priceShift : $this->orderPlacePrice = $ask + $this->priceStep * $this->priceShift);

            Cache::put('orderObject' . env("ASSET_TABLE"), new OrderObject("placeOrder", $direction, $this->orderPlacePrice, $this->orderQuantity, $this->orderId, ""), 5);
            $this->activeOrder = "placed";

            // BD actions
            if($direction == "buy" ){
                DataBase::addOrderRecord($this->orderId);
                DataBase::addOrderInPrice(date("Y-m-d G:i:s"), $this->orderPlacePrice);
            }
            else{
                DataBase::addOrderOutPrice(date("Y-m-d G:i:s"), $this->orderPlacePrice);
            }

        }

        /* When order placed, start to move if needed.
         * For each move volume is calculated accordingly to the volume filled in previous trade.
         */
        if ($this->activeOrder == "new"){

            ($direction == "buy" ? $priceToCheck = $bid - $this->priceStep * $this->priceShift : $priceToCheck = $ask + $this->priceStep * $this->priceShift);
            if ($this->orderPlacePrice != $priceToCheck){

                if ($this->needToMoveOrder){
                    echo "TIME to move the order! " . date("Y-m-d G:i:s") . "Order ID to move: $this->orderId \n";

                    if (time() > $this->rateLimitTime || $this->rateLimitFlag){
                        ($direction == "buy" ? $this->orderPlacePrice = $bid - $this->priceStep * $this->priceShift : $this->orderPlacePrice = $ask + $this->priceStep * $this->priceShift);
                        $tempOrderId = round(microtime(true) * 1000);

                        // Move order
                        // Pass calculated volume
                        Cache::put('orderObject' . env("ASSET_TABLE"), new OrderObject("moveOrder","", $this->orderPlacePrice, $this->orderQuantity, $this->orderId, $tempOrderId), 5);

                        $this->orderId = $tempOrderId;
                        $this->needToMoveOrder = false;

                        $this->rateLimitFlag = false;
                        $this->rateLimitTime = time() + 2; // Move order once in two seconds

                        // On each move - store to price in DB
                        DataBase::addOrderOutExecPrice2($this->orderPlacePrice);
                        echo "Order place price: " . $this->orderPlacePrice . "\n";

                    }
                    else{
                        echo "Trading.php rate limit-------------------- " . date("Y-m-d G:i:s") . "\n";
                    }
                }
            }
        }
    }

    /**
     * Active order state parse.
     * Parse statuses:
     * - new
     * - filled
     * Replaced (moved) order status is handled in parseOrderMove()
     * @param   array @mesage
     * @return  void
     */
    public function parseActiveOrders(array $message){
        /* Order placed */
        if ($message['params']['clientOrderId'] == $this->orderId && $message['params']['status'] == "new" && $this->runOnceFlag){
            // Flag. True by default. Reseted when order filled
            echo __FILE__  . " " . __LINE__ . " Order placed***\n";
            $this->activeOrder = "new";
            $this->runOnceFlag = false; // Enter this IF only once
            if ($message['params']['side'] == 'buy')
                OrderController::addOpenOrder("long", $message['params']['quantity'], $message['params']['price']);
            if ($message['params']['side'] == 'sell'){
                $recodId = OrderController::addEmptyTrade('sell', $message['params']['quantity'], $message['params']['price']);
                OrderController::calculateProfit($recodId);
            }

        }

        /*
         * Order filled.
         * Cases:
         * 1. Full volume execution
         * 2. Partial fill
         */
        //if ($message['params']['clientOrderId'] == $this->orderId && ($message['params']['status'] == "filled" || $message['params']['status'] == "partiallyFilled" )){
        // "reportType" => "trade"
        if ($message['params']['clientOrderId'] == $this->orderId && $message['params']['reportType'] == "trade"){

            dump('Dump from Trading.php 139');
            dump($message);
            Cache::put('orderObject' . env("ASSET_TABLE"), new OrderObject("getActiveOrders"), 5);


            // ** VOL
            array_push($this->tradesArray, new OrderObject("", "", $message['params']['tradePrice'], $message['params']['tradeQuantity']));

            $this->accumulatedOrderVolume = 0;
            foreach ($this->tradesArray as $trade){
                echo "Trading.php 147:\n";
                dump($trade);
                $this->averageOrderFillPrice = $this->averageOrderFillPrice + $trade->price;
                $this->accumulatedOrderVolume += $trade->quantity; // THIS IS WRONG

            }
            $this->averageOrderFillPrice = $this->averageOrderFillPrice / count($this->tradesArray);


            echo "--------------------ACCUMM VOLL: " . $this->accumulatedOrderVolume . "\n";

            $kostylVolume = 90 * $this->orderQuantity / 100;
            echo "KOSTYL VOLUME: " . $kostylVolume . "\n";

            if ($this->accumulatedOrderVolume > $kostylVolume ){
                dump("Trading.php 156. FULL EXEC. Stop thread");
                echo "AVG FILL PRICE/VOLUME: $this->averageOrderFillPrice / $this->accumulatedOrderVolume\n";

                $this->activeOrder = "filled"; // Then we can open a new order
                $this->needToMoveOrder = false; // When order has been filled - don't move it
                echo "THREAD STOP. Order FILLED! filled price: ";
                echo $message['params']['tradePrice'] . " ";
                echo $message['params']['side'] . "\n";
                $this->activeOrder = null; // Test var reset
                Cache::put('commandExit' . env("ASSET_TABLE"), true, 5); // Stop executing this thread
            }
            $this->orderQuantity = $this->orderQuantity - $message['params']['tradeQuantity'];

            // Orders table
            $this->addOrderExecPriceToDB($message);
        }
    }

    /**
     * Determines whether an order should be moved or not.
     * Order statuses requested via websocket method call: getOrders.
     * Order statuses cam be delivered as an array ['orderClientId'], ['status'].
     * This array is foreached in ccxtsocket.php, websocketMessageParse().
     * At each iteration clientOrderId is cheched. This action is needed in order to move only specific order because
     * multiple orders can be be active at the same time under the same account, placed by other bots.
     * @param   array $message
     * @return  void
     *
     */
    public function parseOrderMove(array $message){
        echo "Trading.php ---Order move. this-orderId: " . $this->orderId . " message['clientOrderId']: " . $message['clientOrderId'] . "\n";
        if($this->orderId == $message['clientOrderId']){
            //dump($message[id]);
            echo "need to move this ID!\n";
            $this->needToMoveOrder = true;
        }
        else{
            //dump('as a single valuse: ' . $message['id']);
            echo "dont need to move this id\n";
        }
    }

    public function addOrderExecPriceToDB (array $message){
        if($message['params']['side'] == "buy"){
            //DataBase::addOrderInExecPrice(date("Y-m-d G:i:s", strtotime($message['params']['updatedAt'])), $message['params']['price'], $message['params']['tradeFee']);
        }
        else{
            $recodId = OrderController::addTrade("short", $message['params']['tradeQuantity'], $message['params']['price'], abs($message['params']['tradeFee']));
            OrderController::calculateProfit($recodId);
        }
    }
}

