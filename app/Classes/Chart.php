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

        //$lastTradePrice = // Last trade price
        //    DB::table('asset_1')
        //        ->where('id', $id - 1)->get();

        //echo $barDate . " " . $lastTradePrice->first()->id . "<br>";


        /*
        $timeFrame =
            DB::table('settings_realtime')
                ->where('id', '1')
                ->value('time_frame');
        */

        if ($mode == "backtest")
        {
            // One before last record
            // Backtest mode. ID is sent from Backtest.php
            $penUltimanteRow =
                DB::table('asset_1')
                    ->where('id', $id - 1)
                    ->get() // Get row as a collection. A collection can contain may elements in it
                    ->first(); // Get the first element from the collection. In this case there is only one
        }
        else
        {
            // Realtime mode. No ID of the record is sent. Get the quantity of all records using request

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
                ->where('time_stamp', $timeStamp)
                ->update([
                    // Calculate trade profit only if the position is open.
                    // Because we reach this code all the time when high or low price channel boundary is exceeded
                    'trade_profit' => $tradeProfit,
                ]);
        }



        //echo("\n************************************** new bar issued<br>");
        $this->dateCompeareFlag = true;


        /** Trades watch. Channel value of previous (penultimate bar)*/

        $price_channel_high_value = $penUltimanteRow->price_channel_high_value;
        $price_channel_low_value = $penUltimanteRow->price_channel_low_value;

        $allow_trading =
            DB::table('settings_realtime')
                ->where('id', '1')
                ->value('allow_trading');

        $commisionValue =
            DB::table('settings_tester')
                ->where('id', '1')
                ->value('commission_value');



        //echo $allow_trading . " " . $commisionValue . " " . $price_channel_high_value . " " . $price_channel_low_value;
        //echo $penUltimanteRow[0]->date . " " . $penUltimanteRow[0]->price_channel_high_value . "<br>";

        // If > high price channel. BUY
        // price > price channel
        if (($barClosePrice > $price_channel_high_value) && ($this->trade_flag == "all"
                || $this->trade_flag == "long")){
            echo "####### HIGH TRADE!<br>";

            // trading allowed?
            if ($allow_trading == 1){

                // Is the the first trade ever?
                if ($this->firstEverTradeFlag){
                    // open order buy vol = vol
                    echo "---------------------- FIRST EVER TRADE<br>";
                    app('App\Http\Controllers\PlaceOrder')->index($this->volume,"buy");
                    $this->firstEverTradeFlag = false;
                }
                else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                {
                    // open order buy vol = vol * 2
                    echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2<br>";
                    app('App\Http\Controllers\PlaceOrder')->index($this->volume,"buy");
                    app('App\Http\Controllers\PlaceOrder')->index($this->volume,"buy");
                }
            }
            else{ // trading is not allowed
                $this->firstEverTradeFlag = true;
                echo "---------------------- TRADING NOT ALLOWED<br>";
            }

            $this->trade_flag = "short"; // Trade flag. If this flag set to short -> don't enter this if and wait for channel low crossing (IF below)
            $this->position = "long";
            $this->add_bar_long = true;

            // Add(update) trade info to the last(current) bar(record)
            DB::table('asset_1')
                ->where('time_stamp', $timeStamp)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $barClosePrice,
                    'trade_direction' => "buy",
                    'trade_volume' => $this->volume,
                    'trade_commission' => ($barClosePrice * $commisionValue / 100) * $this->volume,
                    'accumulated_commission' => DB::table('asset_1')->sum('trade_commission') + ($barClosePrice * $commisionValue / 100) * $this->volume,
                ]);

            echo "Trade price: " . $barClosePrice . "<br>";
            $messageArray['flag'] = "buy"; // Send flag to VueJS app.js. On this event VueJS is informed that the trade occurred

        } // BUY trade



        // If < low price channel. SELL
        if (($barClosePrice < $price_channel_low_value) && ($this->trade_flag == "all"  || $this->trade_flag == "short")) { // price < price channel
            echo "####### LOW TRADE!<br>";
            //event(new \App\Events\BushBounce('Short trade!'));

            // trading allowed?
            if ($allow_trading == 1){

                // Is the the first trade ever?
                if ($this->firstEverTradeFlag){
                    // open order buy vol = vol
                    echo "---------------------- FIRST EVER TRADE<br>";
                    //event(new \App\Events\BushBounce('First ever trade'));
                    app('App\Http\Controllers\PlaceOrder')->index($this->volume,"sell");
                    $this->firstEverTradeFlag = false;
                }
                else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                {
                    // open order buy vol = vol * 2
                    echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2<br>";
                    //event(new \App\Events\BushBounce('Not first ever trade'));
                    app('App\Http\Controllers\PlaceOrder')->index($this->volume,"sell");
                    app('App\Http\Controllers\PlaceOrder')->index($this->volume,"sell");
                }
            }
            else{ // trading is not allowed
                $this->firstEverTradeFlag = true;
                echo "---------------------- TRADING NOT ALLOWED<br>";
                //event(new \App\Events\BushBounce('Trading is not allowed'));
            }

            $this->trade_flag = "long";
            $this->position = "short";
            $this->add_bar_short = true;


            // Add(update) trade info to the last(current) bar(record)
            // EXCLUDE THIS CODE TO SEPARATE CLASS!!!!!!!!!!!!!!!!!!!
            DB::table('asset_1')
                ->where('time_stamp', $timeStamp)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $barClosePrice,
                    'trade_direction' => "sell",
                    'trade_volume' => $this->volume,
                    'trade_commission' => ($barClosePrice * $commisionValue / 100) * $this->volume,
                    'accumulated_commission' => DB::table('asset_1')->sum('trade_commission') + ($barClosePrice * $commisionValue / 100) * $this->volume,
                ]);

            echo "Trade price: " . $barClosePrice . "<br>";
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
                ->where('time_stamp', $timeStamp)
                ->value('trade_direction');

        if ($tradeDirection == null && $this->position != null){

            $lastAccumProfitValue =
                DB::table('asset_1')
                    ->whereNotNull('trade_direction')
                    ->orderBy('id', 'desc')
                    ->value('accumulated_profit');

            DB::table('asset_1')
                ->where('time_stamp', $timeStamp) // id of the last record. desc - descent order
                ->update([
                    'accumulated_profit' => $lastAccumProfitValue + $tradeProfit
                ]);

            //echo "Bar with no trade<br>";
            echo "lastAccumProfitValue: " . $lastAccumProfitValue . " tradeProfit: ". $tradeProfit . "<br>";

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
                ->where('time_stamp', $timeStamp) // id of the last record. desc - descent order
                ->update([
                    'accumulated_profit' => $nextToLastDirection + $tradeProfit
                ]);

            echo "Bar with trade. nextToLastDirection: " . $nextToLastDirection;
            //event(new \App\Events\BushBounce('Bar with trade. Direction: ' . $nextToLastDirection));
        }

        /** 1. Skip the first trade. Record 0 to accumulated_profit cell. This code fires once only at the first trade */
        if ($tradeDirection != null && $this->firstPositionEver == true){

            DB::table('asset_1')
                ->where('time_stamp', $timeStamp) // id of the last record. desc - descent order
                ->update([
                    'accumulated_profit' => 0
                ]);

            echo "firstPositionEver!<br>";
            $this->firstPositionEver = false;
        }


        // NET PROFIT net_profit
        if ($this->position != null){

            $accumulatedProfit =
                DB::table('asset_1')
                    ->where('time_stamp', $timeStamp)
                    ->value('accumulated_profit');

            $accumulatedCommission =
                DB::table('asset_1')
                    ->where('time_stamp', $timeStamp)
                    ->value('accumulated_commission');

            DB::table('asset_1')
                ->where('time_stamp', $timeStamp) // Quantity of all records in DB
                ->update([
                    'net_profit' => $accumulatedProfit - $accumulatedCommission
                ]);

        }









    }
}