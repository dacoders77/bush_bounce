<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/14/2018
 * Time: 9:57 PM
 */

namespace App\Classes;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Events\eventTrigger;
use PhpParser\Node\Expr\Variable;


/**
 * Chart class provides collection preparation for chart drawing functionality:
 * History bars (candles)
 * Indicators and diagrams (price channel, volume, profit diagram etc.)
 * Trades (long, short, stop-loss mark)
 * DB actions (trades, profit, accumulated profit etc.)
 * Index method is called on each tick occurrence in RatchetPawlSocket class which reads the trades broadcast stream
 *
 * Tick types in websocket channel:
 * 'te', 'tu' Flags explained
 * 'te' - When the trades is rearrested at the exchange
 * 'tu' - When the actual trade has happened. Delayed for 1-2 seconds from 'te'
 * 'hb' - Heart beating. If there is no new message in the channel for 1 second, Websocket server will send you an heartbeat message in this format
 * SNAPSHOT (the initial message)
 * @see http://blog.bitfinex.com/api/websocket-api-update/
 * @see https://docs.bitfinex.com/docs/ws-general
 */
class Chart
{
    public $trade_flag = "all";
    public $add_bar_long = true; // Count closed position on the same be the signal occurred. The problem is when the position is closed the close price of this bar goes to the next position
    public $add_bar_short = true;
    public $position; // Current position
    public $volume = "0.025"; // Asset amount for order opening
    public $firstPositionEver = true; // Skip the first trade record. When it occurs we ignore calculations and make accumulated_profit = 0. On the next step (next bar) there will be the link to this value
    public $firstEverTradeFlag = true; // True - when the bot is started and the first trade is executed. Then flag turns to false and trade volume is doubled for closing current position and opening the opposite

    /**
     * Received message in RatchetPawlSocket.php is sent to this method as an argument.
     * A message is processed, bars and trades are calculated.
     *
     * @param \Ratchet\RFC6455\Messaging\MessageInterface $socketMessage
     * @param Command Variable type for colored and formatted console messages like alert, warning, error etc.
     * @return array $messageArray Array which has OHLC of the bar, new bar flag and other parameters. The array is
     * generated on each tick (each websocket message) and then passed as an event to the browser. These messages
     * are transmitted over websocket pusher broadcast service.
     * @see https://pusher.com/
     */
    private $z = 0;
    public function index($mode, $barDate, $timeStamp, $barClosePrice, $id)
    {
        echo "**********************************************Chart.php!<br>\n";

        if ($mode == "backtest")
        {
            /** @var int $recordId id of the record in DB
             * In backtest mode id is sent as a parameter. In realtime - pulled from DB
             */
            $recordId = $id;

            // One before last record
            // Backtest mode. ID is sent from Backtest.php
            if (!is_null(DB::table('asset_1')->where('id', $recordId - 1)->get()->first())) // Null check
            {
                $penUltimanteRow =
                    DB::table('asset_1')
                        ->where('id', $recordId - 1)
                        ->get() // Get row as a collection. A collection can contain may elements in it
                        ->first(); // Get the first element from the collection. In this case there is only one
            }
            else{
                echo "NULL object catched. Chart.php line 78";
                die();
            }


        }
        else
        {
            // Realtime mode. No ID of the record is sent. Get the quantity of all records using request
            // Get the price of the last trade
            $recordId = // Last trade price
                DB::table('asset_1')
                    ->whereNotNull('price_channel_high_value')
                    //->where('time_stamp', '<', $timeStamp)
                    ->orderBy('id', 'desc')
                    ->value('id');

            $penUltimanteRow =
                DB::table('asset_1')
                    ->where('id', $recordId - 1)
                    ->get()
                    ->first();
        }


        // Get the price of the last trade
        $lastTradePrice = // Last trade price
            DB::table('asset_1')
                ->whereNotNull('trade_price') // Not null trade price value
                //->where('time_stamp', '<', $timeStamp) // Find the last trade. This check is needed only for historical back testing.
                ->orderBy('id', 'desc') // Form biggest to smallest values
                ->value('trade_price'); // Get trade price value

        // Calculate trade profit
        // Calculate trade profit only if the position is open.
        // Because we reach this code all the time when high or low price channel boundary is exceeded
        $tradeProfit =
            ($this->position != null ?
                (($this->position == "long" ?
                    ($barClosePrice - $lastTradePrice) * $this->volume :
                    ($lastTradePrice - $barClosePrice) * $this->volume)
                ) : false);

        // Do not calculate profit if there is no open position. If do not do this check - zeros in table occurs
        if ($this->position != null){
            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    // Calculate trade profit only if the position is open.
                    // Because we reach this code on each new bar is issued when high or low price channel boundary is exceeded
                    'trade_profit' => round($tradeProfit, 4),
                ]);
        }

        echo "trade profit: " . $tradeProfit . "\n";

        $this->dateCompeareFlag = true;



        /** TRADES WATCH. Channel value of previous (penultimate bar)*/
        /** @todo replace aall $price_channel_low_value variables with $penUltimanteRow->price_channel_low_value*/

        try{
            $price_channel_high_value = $penUltimanteRow->price_channel_high_value; // THIS CODE THROWS THE ERROR: trying to get property on non object
            $price_channel_low_value = $penUltimanteRow->price_channel_low_value;
        }
        catch (exception $exception ) {
            echo "Chart.php line 137: " . $exception;
        }


        $allow_trading =
            DB::table('settings_realtime')
                ->where('id', '1')
                ->value('allow_trading');

        $commisionValue =
            DB::table('settings_tester')
                ->where('id', '1')
                ->value('commission_value');


        echo "penultim:" . $penUltimanteRow->date . " price channel  : " . $penUltimanteRow->price_channel_high_value . "\n";
        echo "bar date:" . $barDate . " bar close price: " .$barClosePrice . "\n";
        echo "$this->trade_flag: " . $this->trade_flag . "\n";



        // If > high price channel. BUY
        // price > price channel
        if (($barClosePrice > $price_channel_high_value) && ($this->trade_flag == "all"
                || $this->trade_flag == "long")){
            echo "####### HIGH TRADE!<br>\n";

            // trading allowed?
            if ($allow_trading == 1){

                // Is the the first trade ever?
                if ($this->firstEverTradeFlag){
                    // open order buy vol = vol
                    echo "---------------------- FIRST EVER TRADE<br>\n";
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder($this->volume,"buy");
                    $this->firstEverTradeFlag = false;
                }
                else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                {
                    // open order buy vol = vol * 2
                    echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2<br>\n";
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder($this->volume,"buy");
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder($this->volume,"buy");
                }
            }
            else{ // trading is not allowed
                $this->firstEverTradeFlag = true;
                echo "---------------------- TRADING NOT ALLOWED\n";
            }

            $this->trade_flag = "short"; // Trade flag. If this flag set to short -> don't enter this IF and wait for channel low crossing (IF below)
            $this->position = "long";
            $this->add_bar_long = true;

            // Update trade info to the last(current) bar(record)
            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $barClosePrice,
                    'trade_direction' => "buy",
                    'trade_volume' => $this->volume,
                    'trade_commission' => round(($barClosePrice * $commisionValue / 100) * $this->volume, 4),
                    'accumulated_commission' => round(DB::table('asset_1')->sum('trade_commission') + ($barClosePrice * $commisionValue / 100) * $this->volume, 4),
                ]);

            echo "Trade price: " . $barClosePrice . "<br>\n";
            $messageArray['flag'] = "buy"; // Send flag to VueJS app.js. On this event VueJS is informed that the trade occurred

        } // BUY trade



        // If < low price channel. SELL
        if (($barClosePrice < $price_channel_low_value) && ($this->trade_flag == "all"  || $this->trade_flag == "short")) { // price < price channel
            echo "####### LOW TRADE!<br>\n";
            //event(new \App\Events\BushBounce('Short trade!'));

            // trading allowed?
            if ($allow_trading == 1){

                // Is the the first trade ever?
                if ($this->firstEverTradeFlag){
                    // open order buy vol = vol
                    echo "---------------------- FIRST EVER TRADE<br>\n";
                    //event(new \App\Events\BushBounce('First ever trade'));
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder($this->volume,"sell");
                    $this->firstEverTradeFlag = false;
                }
                else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                {
                    // open order buy vol = vol * 2
                    echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2<br>\n";
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder($this->volume,"sell");
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder($this->volume,"sell");
                }
            }
            else{ // trading is not allowed
                $this->firstEverTradeFlag = true;
                echo "---------------------- TRADING NOT ALLOWED<br>\n";
                //event(new \App\Events\BushBounce('Trading is not allowed'));
            }

            $this->trade_flag = "long";
            $this->position = "short";
            $this->add_bar_short = true;


            // Add(update) trade info to the last(current) bar(record)
            // EXCLUDE THIS CODE TO SEPARATE CLASS!!!!!!!!!!!!!!!!!!!
            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $barClosePrice,
                    'trade_direction' => "sell",
                    'trade_volume' => $this->volume,
                    'trade_commission' => round(($barClosePrice * $commisionValue / 100) * $this->volume, 4),
                    'accumulated_commission' => round(DB::table('asset_1')->sum('trade_commission') + ($barClosePrice * $commisionValue / 100) * $this->volume, 4),
                ]);


            echo "Trade price: " . $barClosePrice . "<br>\n";
            echo "jopa" . round(($barClosePrice * $commisionValue / 100) * $this->volume, 4) . "\n";
            //die();
            //echo "commisionValue: " . $commisionValue . "<br>";
            //echo "this volume: " . $this->volume . "<br>";
            //echo "percent: " . ($nojsonMessage[2][3] * $commisionValue / 100) . "<br>";
            //echo "result: " . ($nojsonMessage[2][3] * $commisionValue / 100) * $this->volume . "<br>";
            //echo "sum: " . DB::table('asset_1')->sum('trade_commission') . "<br>";

            $messageArray['flag'] = "sell"; // Send flag to VueJS app.js

        } // Sell trade




        // ****RECALCULATED ACCUMULATED PROFIT****
        // Get the if of last row where trade direction is not null

        $tradeDirection =
            DB::table('asset_1')
                ->where('id', $recordId)
                ->value('trade_direction');

        if ($tradeDirection == null && $this->position != null){

            $lastAccumProfitValue =
                DB::table('asset_1')
                    ->whereNotNull('trade_direction')
                    ->orderBy('id', 'desc')
                    ->value('accumulated_profit');

            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'accumulated_profit' => round($lastAccumProfitValue + $tradeProfit, 4)
                ]);

            //echo "Bar with no trade<br>";
            echo "lastAccumProfitValue: " . $lastAccumProfitValue . " tradeProfit: ". $tradeProfit . "<br>\n";

        }

        if ($tradeDirection != null && $this->firstPositionEver == false) // Means that at this bar trade has occurred
        {
            // INTERESTING VERSION OF PENULTIMANTE RECORD!
            $nextToLastDirection =
                DB::table('asset_1')
                    ->whereNotNull('trade_direction')
                    ->orderBy('id', 'desc')->skip(1)->take(1) // Second to last (penultimate). ->get()
                    ->value('accumulated_profit');


            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'accumulated_profit' => $nextToLastDirection + $tradeProfit
                ]);

            //echo "Bar with trade. nextToLastDirection: " . $nextToLastDirection;
            //event(new \App\Events\BushBounce('Bar with trade. Direction: ' . $nextToLastDirection));
        }

        /** 1. Skip the first trade. Record 0 to accumulated_profit cell. This code fires once only at the first trade */
        if ($tradeDirection != null && $this->firstPositionEver == true){

            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'accumulated_profit' => 0
                ]);

            echo "firstPositionEver!<br>\n";
            $this->firstPositionEver = false;
        }


        // NET PROFIT net_profit
        if ($this->position != null){

            $accumulatedProfit =
                DB::table('asset_1')
                    ->where('id', $recordId)
                    ->value('accumulated_profit');
            /*
            $accumulatedCommission =
                DB::table('asset_1')
                    ->where('id', $recordId)
                    ->value('accumulated_commission');
            */
            $accumulatedCommission =
                DB::table('asset_1')
                    ->whereNotNull('accumulated_commission')
                        ->orderBy('id', 'desc')
                        ->value('accumulated_commission');

            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    // net profit = accum_profit - last accum_commission
                    'net_profit' => round($accumulatedProfit - $accumulatedCommission, 4)
                ]);

        }









    }
}