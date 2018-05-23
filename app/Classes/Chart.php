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
    public $dateCompeareFlag = true;
    public $tt; // Time

    public $barHigh = 0; // For high value calculation
    public $barLow = 9999999;

    public $trade_flag = "all";
    public $add_bar_long = true; // Count closed position on the same be the signal occurred. The problem is when the position is closed the close price of this bar goes to the next position
    public $add_bar_short = true;
    public $position; // Current position
    public $volume = "0.025"; // Asset amount for order opening
    public $firstPositionEver = true; // Skip the first trade record. When it occurs we ignore calculations and make accumulated_profit = 0. On the next step (next bar) there will be the link to this value
    public $firstEverTradeFlag = true; // True - when the bot is started and the first trade is executed. Then flag turns to false and trade volume is doubled for closing current position and opening the opposite

    public function __construct()
    {
        $this->timeFrame =
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('time_frame');

        // Get traded symbol from DB. String must look like: tBTCUSD
        // MAKE IT UPPER CASE!
        $this->symbol = "t" .
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('symbol');
    }

    /**
     * Received message in websocket channel is sent to this method as an argument.
     * A message is precessed, bars and trades are calculated.
     *
     * @param \Ratchet\RFC6455\Messaging\MessageInterface $socketMessage
     * @param Command Variable type for colored and formatted console messages like alert, warning, error etc.
     * @return array $messageArray Array which has OHLC of the bar, new bar flag and other parameters. The array is
     * generated on each tick (each websocket message) and then passed as an event to the browser. These messages
     * are transmitted over websocket pusher broadcast service.
     * @see https://pusher.com/
     */
    public function index(\Ratchet\RFC6455\Messaging\MessageInterface $message, Command $command)
    {
        /** First time ever application run check. If so - load historical data first */
        if ((DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('initial_start')))
        {
            echo "Chart.php Application first ever run. Load history data. History::index()\n";
            //event(new \App\Events\BushBounce('Bot first ever run'));
            History::load(); /** After the history is loaded - get price channel calculated (indicator) */
            // Calculate price channel
            \App\Http\Controllers\Realtime\PriceChannel::calculate();
        }

        $jsonMessage = json_decode($message->getPayload(), true); // Methods http://socketo.me/api/class-Ratchet.RFC6455.Messaging.MessageInterface.html
        //print_r($jsonMessage);
        //print_r(array_keys($z));
        //echo $message->__toString() . "\n"; // Decode each message

        if (array_key_exists('chanId',$jsonMessage)){
            $chanId = $jsonMessage['chanId']; // Parsed channel ID then we are gonna listen exactly to this channel number. It changes each time you make a new connection
        }

        $nojsonMessage = json_decode($message->getPayload());

        if (!array_key_exists('event',$jsonMessage)){ // All messages except first two associated arrays
            if ($nojsonMessage[1] == "te") // Only for the messages with 'te' flag. The faster ones
            {
                //echo "id: " . $nojsonMessage[2][0];
                //echo " date: " . gmdate("Y-m-d G:i:s", ($nojsonMessage[2][1] / 1000));
                //echo " volume: " . $nojsonMessage[2][2];
                //echo " price: " . $nojsonMessage[2][3] . "\n";

                // current trade(tick): $nojsonMessage[2][3]
                // volume: $nojsonMessage[2][2]


                // Take seconds off and add 1 min. Do it only once per interval (for example 1min)
                if ($this->dateCompeareFlag) {
                    $x = date("Y-m-d H:i", $nojsonMessage[2][1] / 1000) . "\n"; // Take seconds off. Convert timestamp to date
                    //$this->tt = strtotime($x . $this->timeFrame . 'minute'); // Time frame. Added 1 minute. Timestamp
                    $this->tt = strtotime($x . '+1 minute'); // Time frame. Added 1 minute. Timestamp
                    $this->dateCompeareFlag = false;
                }

                //echo "x: " . $x;
                //echo " this->tt: " . ($this->tt) . "\n";
                //echo " tt: " . gmdate("Y-m-d G:i:s", ($this->tt));
                //die();

                // Make a signal when value reaches over added 1 minute
                echo
                    "Ticker: " . $this->symbol .
                    " time: " . gmdate("Y-m-d G:i:s", ($nojsonMessage[2][1] / 1000)) .
                    " price: " . $nojsonMessage[2][3] .
                    " vol: " . $nojsonMessage[2][2] .
                    " pos: " . $this->position . "\n";

                /*
                event(new \App\Events\BushBounce(
                    $this->symbol .
                    " / " . gmdate("G:i:s", ($nojsonMessage[2][1] / 1000)) .
                    " price: " . $nojsonMessage[2][3] .
                    " pos: " . $this->position
                ));
                */

                // Calculate high and low of the bar then pass it to the chart in $messageArray
                if ($nojsonMessage[2][3] > $this->barHigh) // High
                {
                    $this->barHigh = $nojsonMessage[2][3];
                }

                if ($nojsonMessage[2][3] < $this->barLow) // Low
                {
                    $this->barLow = $nojsonMessage[2][3];
                }

                // RATCHET ERROR GOES HERE, WHILE INITIAL START FROM GIU. trying to get property of non-object
                // Update high, low and close of the current bar in DB. Update the record on each trade.
                // Then the new bar will be issued - we will have actual values updated in the DB

                // ERROR: Trying to get property of non object
                // Occurs when ratchet:start is run for the first time and the history table is empty - no record to update
                // Start GIU first and then rathcet:start
                // Updating last bar in the table. At the first run the table is not empty. Historical bars were loaded




                    try {
                    DB::table(env("ASSET_TABLE"))
                        ->where('id', DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id) // id of the last record. desc - descent order
                        ->update([
                            'close' => $nojsonMessage[2][3],
                            'high' => $this->barHigh,
                            'low' => $this->barLow,
                        ]);
                    }
                    catch(Exception $e) {
                        echo 'Error while DB record update: ' .$e->getMessage();
                        //event(new \App\Events\BushBounce('Error while DB record update: ' .$e->getMessage()));
                    }


                //echo "current tick: " . gmdate("Y-m-d G:i:s", ($nojsonMessage[2][1] / 1000));
                //echo " time to comapre: " . gmdate("Y-m-d G:i:s", ($this->tt / 1000));

                // NEW BAR IS ISSUED
                if (floor(($nojsonMessage[2][1] / 1000)) >= $this->tt){

                    // Experiment
                    // Add new bar to the DB
                    DB::table(env("ASSET_TABLE"))->insert(array( // Record to DB
                        'date' => gmdate("Y-m-d G:i:s", ($nojsonMessage[2][1] / 1000)), // Date in regular format. Converted from unix timestamp
                        'time_stamp' => $nojsonMessage[2][1],
                        'open' => $nojsonMessage[2][3],
                        'close' => $nojsonMessage[2][3],
                        'high' => $nojsonMessage[2][3],
                        'low' => $nojsonMessage[2][3],
                        'volume' => $nojsonMessage[2][2],
                    ));

                    // Get the price of the last trade
                    $lastTradePrice = // Last trade price
                        DB::table(env("ASSET_TABLE"))
                            ->whereNotNull('trade_price') // not null trade price value
                            ->orderBy('id', 'desc') // form biggest to smallest values
                            ->value('trade_price'); // get trade price value


                    // Calculate trade profit
                    $tradeProfit = ($this->position != null ? (($this->position == "long" ? ($nojsonMessage[2][3] - $lastTradePrice) * $this->volume : ($lastTradePrice - $nojsonMessage[2][3]) * $this->volume)) : false); // Calculate trade profit only if the position is open. Because we reach this code all the time when high or low price channel boundary is exceeded

                    if ($this->position != null){ // Do not calculate progit if there is not open position. If do not do this check - zeros in table occurs
                        DB::table(env("ASSET_TABLE"))
                            ->where('id', DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id)
                            ->update([
                                // Calculate trade profit only if the position is open. Because we reach this code all the time when high or low price channel boundary is exceeded
                                'trade_profit' => $tradeProfit,
                            ]);
                    }

                    $command->error("\n************************************** new bar issued");
                    //event(new \App\Events\BushBounce('New bar issued'));
                    $messageArray['flag'] = true; // Added true flag which will inform JS that new bar is issued
                    $this->dateCompeareFlag = true;



                    // Trades watch
                    // Quantity of all records in DB
                    $x = (DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->get())[0]->id;

                    // Get price
                    // Channel value of previous (penultimate bar)
                    $price_channel_high_value =
                        DB::table(env("ASSET_TABLE"))
                            ->where('id', ($x - 1)) // Penultimate record. One before last
                            ->value('price_channel_high_value');

                    $price_channel_low_value =
                        DB::table(env("ASSET_TABLE"))
                            ->where('id', ($x - 1)) // Penultimate record. One before last
                            ->value('price_channel_low_value');

                    $allow_trading =
                        DB::table('settings_realtime')
                            ->where('id', env("SETTING_ID"))
                            ->value('allow_trading');

                    $commisionValue =
                        DB::table('settings_tester')
                            ->where('id', env("SETTING_ID"))
                            ->value('commission_value');


                    // If > high price channel. BUY
                    // price > price channel
                    if (($nojsonMessage[2][3] > $price_channel_high_value) && ($this->trade_flag == "all" || $this->trade_flag == "long")){
                        echo "####### HIGH TRADE!\n";
                        //event(new \App\Events\BushBounce('Long trade has occurred!'));

                        // trading allowed?
                        if ($allow_trading == 1){

                            // Is the the first trade ever?
                            if ($this->firstEverTradeFlag){
                                // open order buy vol = vol
                                echo "---------------------- FIRST EVER TRADE\n";
                                //event(new \App\Events\BushBounce('First ever trade'));
                                app('App\Http\Controllers\PlaceOrder')->index($this->volume,"buy");
                                $this->firstEverTradeFlag = false;
                            }
                            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                            {
                                // open order buy vol = vol * 2
                                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2\n";
                                //event(new \App\Events\BushBounce('Not the first ever trade'));
                                app('App\Http\Controllers\PlaceOrder')->index($this->volume,"buy");
                                app('App\Http\Controllers\PlaceOrder')->index($this->volume,"buy");
                            }
                        }
                        else{ // trading is not allowed
                            $this->firstEverTradeFlag = true;
                            echo "---------------------- TRADING NOT ALLOWED\n";
                            //event(new \App\Events\BushBounce('Trading is not allowed'));
                        }



                        $this->trade_flag = "short"; // Trade flag. If this flag set to short -> don't enter this if and wait for channel low crossing (IF below)
                        $this->position = "long";
                        $this->add_bar_long = true;


                        // Add(update) trade info to the last(current) bar(record)
                        DB::table(env("ASSET_TABLE"))
                            ->where('id', $x)
                            ->update([
                                'trade_date' => gmdate("Y-m-d G:i:s", ($nojsonMessage[2][1] / 1000)),
                                'trade_price' => $nojsonMessage[2][3],
                                'trade_direction' => "buy",
                                'trade_volume' => $this->volume,
                                'trade_commission' => ($nojsonMessage[2][3] * $commisionValue / 100) * $this->volume,
                                'accumulated_commission' => DB::table(env("ASSET_TABLE"))->sum('trade_commission') + ($nojsonMessage[2][3] * $commisionValue / 100) * $this->volume,
                            ]);

                        echo "nojsonMessage[2][3]: " . $nojsonMessage[2][3] . "\n";
                        echo "commisionValue: " . $commisionValue . "\n";
                        echo "this volume: " . $this->volume . "\n";
                        echo "percent: " . ($nojsonMessage[2][3] * $commisionValue / 100) . "\n";
                        echo "result: " . ($nojsonMessage[2][3] * $commisionValue / 100) * $this->volume . "\n";
                        echo "sum: " . DB::table(env("ASSET_TABLE"))->sum('trade_commission') . "\n";

                        $messageArray['flag'] = "buy"; // Send flag to VueJS app.js. On this event VueJS is informed that the trade occurred

                    } // BUY trade





                    // If < low price channel. SELL
                    if (($nojsonMessage[2][3] < $price_channel_low_value) && ($this->trade_flag == "all"  || $this->trade_flag == "short")) { // price < price channel
                        echo "####### LOW TRADE!\n";
                        //event(new \App\Events\BushBounce('Short trade!'));

                        // trading allowed?
                        if ($allow_trading == 1){

                            // Is the the first trade ever?
                            if ($this->firstEverTradeFlag){
                                // open order buy vol = vol
                                echo "---------------------- FIRST EVER TRADE\n";
                                //event(new \App\Events\BushBounce('First ever trade'));
                                app('App\Http\Controllers\PlaceOrder')->index($this->volume,"sell");
                                $this->firstEverTradeFlag = false;
                            }
                            else // Not the first trade. Close the current position and open opposite trade. vol = vol * 2
                            {
                                // open order buy vol = vol * 2
                                echo "---------------------- NOT FIRST EVER TRADE. CLOSE + OPEN. VOL*2\n";
                                //event(new \App\Events\BushBounce('Not first ever trade'));
                                app('App\Http\Controllers\PlaceOrder')->index($this->volume,"sell");
                                app('App\Http\Controllers\PlaceOrder')->index($this->volume,"sell");
                            }
                        }
                        else{ // trading is not allowed
                            $this->firstEverTradeFlag = true;
                            echo "---------------------- TRADING NOT ALLOWED\n";
                            //event(new \App\Events\BushBounce('Trading is not allowed'));
                        }

                        $this->trade_flag = "long";
                        $this->position = "short";
                        $this->add_bar_short = true;


                        // Add(update) trade info to the last(current) bar(record)
                        // EXCLUDE THIS CODE TO SEPARATE CLASS!!!!!!!!!!!!!!!!!!!
                        DB::table(env("ASSET_TABLE"))
                            ->where('id', $x)
                            ->update([
                                'trade_date' => gmdate("Y-m-d G:i:s", ($nojsonMessage[2][1] / 1000)),
                                'trade_price' => $nojsonMessage[2][3],
                                'trade_direction' => "sell",
                                'trade_volume' => $this->volume,
                                'trade_commission' => ($nojsonMessage[2][3] * $commisionValue / 100) * $this->volume,
                                'accumulated_commission' => DB::table(env("ASSET_TABLE"))->sum('trade_commission') + ($nojsonMessage[2][3] * $commisionValue / 100) * $this->volume,
                            ]);

                        echo "nojsonMessage[2][3]: " . $nojsonMessage[2][3] . "\n";
                        echo "commisionValue: " . $commisionValue . "\n";
                        echo "this volume: " . $this->volume . "\n";
                        echo "percent: " . ($nojsonMessage[2][3] * $commisionValue / 100) . "\n";
                        echo "result: " . ($nojsonMessage[2][3] * $commisionValue / 100) * $this->volume . "\n";
                        echo "sum: " . DB::table(env("ASSET_TABLE"))->sum('trade_commission') . "\n";

                        $messageArray['flag'] = "sell"; // Send flag to VueJS app.js

                    } // Sell trade




                    // ****RECALCULATED ACCUMULATED PROFIT****
                    // Get the if of last row where trade direction is not null

                    $tradeDirection =
                        DB::table(env("ASSET_TABLE"))
                            ->where('id', (DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id))
                            ->value('trade_direction');

                    if ($tradeDirection == null && $this->position != null){

                        $lastAccumProfitValue =
                            DB::table(env("ASSET_TABLE"))
                                ->whereNotNull('trade_direction')
                                ->orderBy('id', 'desc')
                                ->value('accumulated_profit');
                        DB::table(env("ASSET_TABLE"))
                            ->where('id', DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id) // id of the last record. desc - descent order
                            ->update([
                                'accumulated_profit' => $lastAccumProfitValue + $tradeProfit
                                //'accumulated_profit' => 789789
                            ]);

                        echo "Bar with no trade";
                        echo "lastAccumProfitValue: " . $lastAccumProfitValue . " tradeProfit: ". $tradeProfit;

                    }

                    if ($tradeDirection != null && $this->firstPositionEver == false) // Means that at this bar trade has occurred
                    {

                        $nextToLastDirection =
                            DB::table(env("ASSET_TABLE"))
                                ->whereNotNull('trade_direction')
                                ->orderBy('id', 'desc')->skip(1)->take(1) // Second to last (penultimate). ->get()
                                ->value('accumulated_profit');


                        DB::table(env("ASSET_TABLE"))
                            ->where('id', DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id) // id of the last record. desc - descent order
                            ->update([
                                'accumulated_profit' => $nextToLastDirection + $tradeProfit
                            ]);

                        echo "Bar with trade. nextToLastDirection: " . $nextToLastDirection;
                        //event(new \App\Events\BushBounce('Bar with trade. Direction: ' . $nextToLastDirection));
                    }

                    /** 1. Skip the first trade. Record 0 to accumulated_profit cell. This code fires once only at the first trade */
                    if ($tradeDirection != null && $this->firstPositionEver == true){

                        DB::table(env("ASSET_TABLE"))
                            ->where('id', DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id) // id of the last record. desc - descent order
                            ->update([
                                'accumulated_profit' => 0
                            ]);

                        echo "firstPositionEver!";
                        //event(new \App\Events\BushBounce('First position(trade) ever'));
                        $this->firstPositionEver = false;

                    }






                    // NET PROFIT net_profit
                    if ($this->position != null){

                        $accumulatedProfit =
                            DB::table(env("ASSET_TABLE"))
                                ->where('id', (DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id))
                                ->value('accumulated_profit');

                        $accumulatedCommission =
                            DB::table(env("ASSET_TABLE"))
                                ->where('id', (DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id))
                                ->value('accumulated_commission');

                        DB::table(env("ASSET_TABLE"))
                            ->where('id', DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id) // Quantity of all records in DB
                            ->update([
                                'net_profit' => $accumulatedProfit - $accumulatedCommission
                            ]);

                    }



                    /** Recalculate price channel. Controller call as a method */
                    //app('App\Http\Controllers\indicatorPriceChannel')->index();

                } // New bar is issued

                /** Add calculated values to associative array */
                $messageArray['tradeId'] = $nojsonMessage[2][0]; // $messageArray['flag'] = true; And all these values will be sent to VueJS
                $messageArray['tradeDate'] = $nojsonMessage[2][1];
                $messageArray['tradeVolume'] = $nojsonMessage[2][2];
                $messageArray['tradePrice'] = $nojsonMessage[2][3];
                $messageArray['tradeBarHigh'] = $this->barHigh; // Bar high
                $messageArray['tradeBarLow'] = $this->barLow; // Bar Low


                /** Send filled associated array in the event as the parameter */
                event(new \App\Events\BushBounce($messageArray));
                //event(new eventTrigger($messageArray));

                /** Reset high, low of the bar but do not out send these values to the chart. Next bar will be started from scratch */
                if ($this->dateCompeareFlag == true){
                    $this->barHigh = 0;
                    $this->barLow = 9999999;
                }
            }
        }
    }
}