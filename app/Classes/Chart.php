<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/14/2018
 * Time: 9:57 PM
 */

namespace App\Classes;

use App\Jobs\PlaceLimitOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Events\eventTrigger;
use PhpParser\Node\Expr\Variable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;


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
    public $trade_flag; // The value is stored in DB. This flag indicates what trade should be opened next. When there is not trades, it is set to all. When long trade has been opened, the next (closing) one must be long and vise vera.
    public $add_bar_long = true; // Count closed position on the same be the signal occurred. The problem is when the position is closed the close price of this bar goes to the next position
    public $add_bar_short = true;
    public $position; // Current position
    public $volume; // Asset amount for order opening
    public $firstPositionEver = true; // Skip the first trade record. When it occurs we ignore calculations and make accumulated_profit = 0. On the next step (next bar) there will be the link to this value
    public $firstEverTradeFlag; // True - when the bot is started and the first trade is executed. Then flag turns to false and trade volume is doubled for closing current position and opening the opposite
    public $tradeProfit;


    /**
     * Received message in RatchetPawlSocket.php is sent to this method as an argument.
     * A message is processed, bars are added to DB, profit is calculated.
     *
     * @param \Ratchet\RFC6455\Messaging\MessageInterface $socketMessage
     * @param Command Variable type for colored and formatted console messages like alert, warning, error etc.
     * @return array $messageArray Array which has OHLC of the bar, new bar flag and other parameters. The array is
     * generated on each tick (each websocket message) and then passed as an event to the browser. These messages
     * are transmitted over websocket pusher broadcast service.
     * @see https://pusher.com/
     * @see Classes and backtest scheme https://drive.google.com/file/d/1IDBxR2dWDDsbFbradNapSo7QYxv36EQM/view?usp=sharing
     */
    private $z = 0;
    //public function index($mode, $barDate, $timeStamp, $barClosePrice, $id)
    public function index($mode, $barDate, $timeStamp, $bbbbb, $id)
    {
        echo "**********************************************Chart.php!<br>\n";

        /**
         * @todo Read the whole settings row and access it with keys.
         * No need to use 2 requests - bad coding style.
         */
        $this->volume = DB::table('settings_realtime')->where('id', 1)->value('volume');
        $this->trade_flag = DB::table('settings_realtime')->where('id', 1)->value('trade_flag');




        if ($mode == "backtest")
        {
            /** @var int $recordId id of the record in DB
             * In backtest mode id is sent as a parameter. In realtime - pulled from DB.
             * Then this id is used a record ID in order to access records in DB
             */
            $recordId = $id;

            /**
             * @var $assetRow array contains a record from DB with contains bar close, sma value, profit etc.
             * We need bar close price in two cases: history backtest and realtime.
             * In this case - backtest, we already have full history in the DB (many records in the DB) and id is
             * generated from Backtest.php
             */
            $assetRow =
                DB::table('asset_1')
                    ->where('id', $id)
                    ->get();
        }
        else // Realtime
        {
            // Realtime mode. No ID of the record is sent. Get the quantity of all records using request
            /** In this case we do the same request, take the last record from the DB */
            $assetRow =
                DB::table('asset_1')
                    ->orderBy('id', 'desc')->take(1)
                    ->get();

            $recordId = $assetRow[0]->id;

        }

        $barClosePrice = $assetRow[0]->sma;



        /** We do this check because sometimes, don't really understand under which circumstances, we get
         * trying to get property of non-object
         */
        if (!is_null(DB::table('asset_1')->where('id', $recordId - 1)->get()->first()))
        {
            // One before last record
            // Backtest mode. ID is sent from Backtest.php
            $penUltimanteRow =
                DB::table('asset_1')
                    ->where('id', $recordId - 1)
                    ->get() // Get row as a collection. A collection can contain may elements in it
                    ->first(); // Get the first element from the collection. In this case there is only one
        }
        else
        {
            echo "Null check. Chart.php line 132";
            event(new \App\Events\ConnectionError("Excp catch! Chart.php line 132. Null check penultimate"));
            //die();
        }


        // Do not calculate profit if there is no open position. If do not do this check - zeros in table occur
        // $this->trade_flag != "all" if it is "all" - it means that it is a first or initial start
        // We do not store position in DB thus we use "all" check to determine a position absence
        // if "all" - no position has been opened yet
        if ($this->position != null && $this->trade_flag != "all"){

            // Get the price of the last trade
            $lastTradePrice = // Last trade price
                DB::table('asset_1')
                    ->whereNotNull('trade_price') // Not null trade price value
                    //->where('time_stamp', '<', $timeStamp) // Find the last trade. This check is needed only for historical back testing.
                    ->orderBy('id', 'desc') // Form biggest to smallest values
                    ->value('trade_price'); // Get trade price value


            $this->tradeProfit =
                    (($this->position == "long" ?
                        ($assetRow[0]->close - $lastTradePrice) * $this->volume :
                        ($lastTradePrice - $assetRow[0]->close) * $this->volume)
                    );

            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    // Calculate trade profit only if the position is open.
                    // Because we reach this code on each new bar is issued when high or low price channel boundary is exceeded
                    'trade_profit' => round($this->tradeProfit, 4),
                ]);

            //event(new \App\Events\ConnectionError("INFO. Chart.php line 164. trade profit calculated "));
            echo "trade profit calculated. Chart.php line 165: " . $this->tradeProfit . "\n";
        }

        $this->dateCompeareFlag = true;

        /** TRADES WATCH. Channel value of previous (penultimate bar)*/

        /**
         * @todo Read the whole row as a single collection then access it by keys. No need to make several request. Get rid of settings_tester
         */
        $allow_trading =
            DB::table('settings_realtime')
                ->where('id', '1')
                ->value('allow_trading');

        $commisionValue =
            DB::table('settings_tester')
                ->where('id', '1')
                ->value('commission_value');


        // If > high price channel. BUY
        // price > price channel
        // $this->trade_flag == "all" is used only when the first trade occurs, then it turns to "long" or "short".
        // When the trade is about to happen we don't know yet
        // whether it is gonna be long or short. This condition allows to enter both IF, long and short.

        if (($barClosePrice > $penUltimanteRow->price_channel_high_value) &&
            ($this->trade_flag == "all" || $this->trade_flag == "long")){
            echo "####### HIGH TRADE!<br>\n";
            Log::debug("Chart.php line 193. ####### HIGH TRADE!. Bar closed higher than upper price channel. barClosePrice: $barClosePrice");

            // Trading allowed? This value is pulled from DB. If false orders are not sent to the exchange
            if ($allow_trading == 1){

                // Is it the first trade ever?
                if ($this->trade_flag == "all"){
                    // open order buy vol = vol
                    echo "---------------------- FIRST EVER TRADE<br>\n";
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder("buy"); // Works good
                    event(new \App\Events\ConnectionError("INFO. Chart.php line 211. BUY ORDER. "));
                }
                else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                {
                    // open order buy vol = vol * 2
                    echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2<br>\n";
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder("buy");
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder("buy");
                    event(new \App\Events\ConnectionError("INFO. Chart.php line 222 . BUY ORDER. "));
                }
            }
            else{ // trading is not allowed
                echo "---------------------- TRADING NOT ALLOWED\n";

                // Start placing limit order
                //Artisan::call('ccxtd:start', ['direction' => 'buy']);
                //PlaceLimitOrder::dispatch('buy')->onQueue('orders');
                Artisan::queue('ccxt:start', ['--buy' => true]);
            }

            // Trade flag. If this flag set to short -> don't enter this IF and wait for channel low crossing (IF below)
            DB::table("settings_realtime")->where('id', 1)->update(['trade_flag' => 'short']);

            $this->position = "long";
            $this->add_bar_long = true;

            // Update trade info to the last(current) bar(record)
            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $assetRow[0]->close,
                    'trade_direction' => "buy",
                    'trade_volume' => $this->volume,
                    'trade_commission' => round(($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                    'accumulated_commission' => round(DB::table('asset_1')->sum('trade_commission') + ($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                ]);

            echo "Trade price: " . $assetRow[0]->close . "<br>\n";
            $messageArray['flag'] = "buy"; // Send flag to VueJS app.js. On this event VueJS is informed that the trade occurred

        } // BUY trade



        // If < low price channel. SELL
        if (($barClosePrice < $penUltimanteRow->price_channel_low_value) &&
            ($this->trade_flag == "all"  || $this->trade_flag == "short")) {
            echo "####### LOW TRADE!<br>\n";
            Log::debug("Chart.php line 193. ####### LOW TRADE!. Bar closed lower than lower channel. barClosePrice: $barClosePrice");

            // trading allowed?
            if ($allow_trading == 1){

                // Is the the first trade ever?
                if ($this->trade_flag == "all"){
                    // open order buy vol = vol
                    echo "---------------------- FIRST EVER TRADE<br>\n";
                    //event(new \App\Events\BushBounce('First ever trade'));
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder("sell");
                    event(new \App\Events\ConnectionError("INFO. Chart.php line 274. SELL ORDER. "));
                }
                else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                {
                    // open order buy vol = vol * 2
                    echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2<br>\n";
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder("sell");
                    app('App\Http\Controllers\PlaceOrder\BitFinexAuthApi')->placeOrder("sell");
                    event(new \App\Events\ConnectionError("INFO. Chart.php line 285. SELL ORDER. "));

                }
            }
            else{ // trading is not allowed
                echo "---------------------- TRADING NOT ALLOWED<br>\n";
                // Start placing limit order
                //Artisan::call('ccxtd:start', ['direction' => 'sell']);
                //PlaceLimitOrder::dispatch('sell')->onQueue('orders');

                Artisan::queue('ccxt:start', ['--buy' => false]);
            }

            DB::table("settings_realtime")->where('id', 1)->update(['trade_flag' => 'long']);
            $this->position = "short";
            $this->add_bar_short = true;


            // Add(update) trade info to the last(current) bar(record)
            // EXCLUDE THIS CODE TO SEPARATE CLASS!!!!!!!!!!!!!!!!!!!
            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'trade_date' => gmdate("Y-m-d G:i:s", ($timeStamp / 1000)),
                    'trade_price' => $assetRow[0]->close,
                    'trade_direction' => "sell",
                    'trade_volume' => $this->volume,
                    'trade_commission' => round(($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                    'accumulated_commission' => round(DB::table('asset_1')->sum('trade_commission') + ($assetRow[0]->close * $commisionValue / 100) * $this->volume, 4),
                ]);

            $messageArray['flag'] = "sell"; // Send flag to VueJS app.js

        } // Sell trade


        /**
         * Accumulated profit
         *
         * Complicated code for getting the accumulated profit done.
         * We follow these steps in order to calculate accumulated profit
         * 1. We get the whole record using id. id is received in two ways:
         * a) History testing. id is pulled from DB in Backtest.php
         * b) Real-time trading. id is read from the last row from DB ($assetRow).
         * 2. Then we filter all records from the DB where trade_direction field in not null. It means that the trade was executed on that bar.
         * 3. On each bar we decide whether there is a trade on that bar or it is a bar with no trade (trade was executed before)
         * 4. If there is a trade (trade_direction = buy or sell) we use two accumulated_profit values back (index - 2)
         * 4. If there is a trade (trade_direction = buy or sell) we use two accumulated_profit values back (index - 2)
         * 5. If these no trade (else) we take the previous accumulate_profit value
         * 6. When a history testing (or real-time trading) has just started we are having bars with trade_direction = null.
         * in this case we use ternary operator to handle it ( condition ? if true : if false).
         */


        if ($this->trade_flag != "all") {

            /** @var int $z Get the last record from the asset table */
            $z =
                DB::table('asset_1')
                    ->where('id', $recordId)
                    ->get();
            /** @var array $temp The revious record from asset table where trade_direction is not null */
            $temp =
                DB::table('asset_1')
                    //->where('id', $z->id - 1)
                    ->whereNotNull('trade_direction')
                    ->get();

            // Need to check whether $assetRow and $z are equal
            Log::debug("asset_row: " . json_encode($assetRow) );
            Log::debug("asset_row: " . json_encode($z) );


            /** On this code we get this error: Trying to get property of non object */
            if ($z[0]->trade_direction == "buy" || $z[0]->trade_direction == "sell") {

                //Log::debug("Chart.php line 341: " . (count($temp) > 1 ? $temp[count($temp) - 2]->accumulated_profit + $this->tradeProfit : 666) . " last id: " . $z[0]->trade_direction);

                DB::table('asset_1')
                    ->where('id', $recordId)
                    ->update([
                        'accumulated_profit' => (count($temp) > 1 ? $temp[count($temp) - 2]->accumulated_profit + $this->tradeProfit : 0)
                    ]);

            } else
            {
                //Log::debug("jo: " . (count($temp) > 1 ? $temp[count($temp) - 1]->id  : 888) );

                DB::table('asset_1')
                    ->where('id', $recordId)
                    ->update([
                        'accumulated_profit' => (count($temp) > 1 ? $temp[count($temp) - 1]->accumulated_profit + $this->tradeProfit : 0)
                    ]);
            }





        }

        /** @todo try to remove this $firstPositionEver flag */
        if ($this->trade_flag != "all" && $this->firstPositionEver == false){
        //if ($tradeDirection != null && $this->firstPositionEver == false) // Means that at this bar trade has occurred

            /*
            // INTERESTING VERSION OF PENULTIMANTE RECORD.
            $nextToLastDirection =
                DB::table('asset_1')
                    ->whereNotNull('trade_direction')
                    ->orderBy('id', 'desc')->skip(1)->take(1) // Second to last (penultimate). ->get()
                    ->value('accumulated_profit');

            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'accumulated_profit' => $nextToLastDirection + $this->tradeProfit
                ]);
            */

            //echo "Bar with trade. nextToLastDirection: " . $nextToLastDirection;
            //event(new \App\Events\BushBounce('Bar with trade. Direction: ' . $nextToLastDirection));
        }


        /** 1. Skip the first trade. Record 0 to accumulated_profit cell. This code fires once only at the first trade */

        if ($this->trade_flag != "all" && $this->firstPositionEver == true){
        //if ($tradeDirection != null && $this->firstPositionEver == true){

            DB::table('asset_1')
                ->where('id', $recordId)
                ->update([
                    'accumulated_profit' => 0
                ]);
            $this->firstPositionEver = false;
        }


        // NET PROFIT net_profit
        if ($this->position != null){

            $accumulatedProfit =
                DB::table('asset_1')
                    ->where('id', $recordId)
                    ->value('accumulated_profit');

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