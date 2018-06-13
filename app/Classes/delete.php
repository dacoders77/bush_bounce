<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/12/2018
 * Time: 8:41 PM
 */












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






























$timeFrame =
    DB::table('settings_realtime')
        ->where('id', '1')
        ->value('time_frame');

// One before last record
$penUltimanteRow =
    DB::table('asset_1')
        //->where('time_stamp', $timeStamp - ($timeFrame * 60 * 1000)) // not null trade price value
        //->value('date') // get trade price value
        ->get();

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

$price_channel_high_value = $penUltimanteRow[0]->price_channel_high_value;
$price_channel_low_value = $penUltimanteRow[0]->price_channel_low_value;

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
    //echo "commisionValue: " . $commisionValue . "<br>";
    //echo "this volume: " . $this->volume . "<br>";
    //echo "percent: " . ($barClosePrice * $commisionValue / 100) . "<br>";
    //echo "result: " . ($barClosePrice * $commisionValue / 100) * $this->volume . "<br>";
    //echo "sum: " . DB::table('asset_1')->sum('trade_commission') . "<br>";

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

