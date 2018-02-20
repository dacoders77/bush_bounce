<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;


/**
 * Class loadJsonFromDB
 * @package App\Http\Controllers
 *
 * This class is called from chart's JS via AJAX request.
 * The class loads the historical
 */
class loadJsonFromDB extends Controller
{
    public function get() // $z variable is called from js AJAX request from chart_view.blade.php
    {

        $element_index = 0; // Index for loop through all elements in interval for which price channel is calculated
        $max_value = 0;
        $low_value = 999999;
        $accumulated_profit = 0; // At the beggining = 0 and recorded when the trade is closd. The nex trade's profit starts from this value
        $long_trade_flag = true; // When the long trade is open this flag turns to false. When the long trade is open we must ignore all high price channel boundary and wait until it is closed
        $short_trade_flag = true;
        $trade_flag = "all";
        $profit_diagram = null; // We have to make it null. Otherwise in cases when there is no trades at the chart and thus no $profit_diagram is calculated - the error is thrown.


        $add_bar_long = true; // Count closed position on the same be the signal occurred. The problem is when the position is closed the close price of this bar goes to the next position
        $add_bar_short = true;

        $short_trades = [];
        $long_trades = [];

        // Variables for strategy testing parameters (results)
        $initial_capital = 0;

        $profit_trades = 0;
        $loss_trades = 0;

         // Starting value for local drawdown
        $tempDrawDown = 0;
        $localDrawDown = 0;


//************
        $StartUpAsset = DB::table('assets')->where('show_on_startup', 1)->value('asset_name'); // We take only one asset from the DB. The one which has show_on_startup flag
        $price_channel_interval = DB::table('assets')->where('asset_name', $StartUpAsset)->value('price_channel_default_value');
        $message [] = ["interval: " . $price_channel_interval]; // Add variable to the message array for debugging purpuse

        $all_table_values = DB::table('history_' . $StartUpAsset)->get(); // Read the whole table from BD to $all_table_values variable


        foreach ($all_table_values as $row_values) { // Go through all records loaded from the DB

            // Add the candle to the array. Main candlestick chart data. Put all values from the table to this array
            $data[] = [$row_values->time_stamp, $row_values->open, $row_values->high, $row_values->low, $row_values->close];

            // Start from $price_channel_interval - 1 element. - 1 because elements in arrays are named from 0. We don't start calculating price channel from the first candle
            if ($element_index >= $price_channel_interval - 1)
            {
                // Cycle backwards through elements ($price_channel_interval) for calculating price channel
                for ($i = $element_index ; $i > $element_index - $price_channel_interval; $i--)
                {

                    if ($all_table_values[$i]->high > $max_value) // Find max value in interval
                        $max_value = $all_table_values[$i]->high;

                    if ($all_table_values[$i]->low < $low_value) // Find low value in interval
                        $low_value = $all_table_values[$i]->low;

                }

                $arr1[] = [$all_table_values[$element_index]->time_stamp, $max_value]; // Added found max value to the array
                $arr2[] = [$all_table_values[$element_index]->time_stamp, $low_value];



                // Trades testing. Adding trades to $long_trades[] and $short_trades[] for output to the chart
                if ($element_index >= $price_channel_interval) // We start from the next element after which the high value(price channel) has been calculated
                {
                    // Long && ($trade_flag == "all")

                    if ($all_table_values[$element_index]->close > $arr1[$element_index - $price_channel_interval][1] && ($trade_flag == "all" || $trade_flag == "long")) {

                        $long_trades[] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->close]; // Added long marker
                        $long_trade_flag = false;

                        $trade_flag = "short";
                        $position = "long";
                        $add_bar_long = true;
                        //$message []  = [$all_table_values[$element_index]->close];
                    }

                    // Short
                    if ($all_table_values[$element_index]->close < $arr2[$element_index - $price_channel_interval][1] && ($trade_flag == "all"  || $trade_flag == "short")) {

                        $short_trades[] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->close]; // Added short marker
                        $long_trade_flag = true;

                        $trade_flag = "long";
                        $position = "short";
                        $add_bar_short = true;
                        //$message []  = [$all_table_values[$element_index]->close];
                    }




                    // Profit chart data calculation
                    // Strategiy testing parameters(results) calculation


                    if (count($long_trades) || count($short_trades)) // Start calculating profit values only after the trade has occurred. $short_trades or $long_trades array is not empty
                    {

                        if ($position == "long") // If the long position is open. $position
                        {
                            // Position changes and this is not the first trade at the chart (count($short_trades) != 0)
                            // When the position is changed, on the bar on which the signal happened (close > max), we need to proceed this last bar with the same trade direction.
                            // When the signal happens this bar is already in the oppoite trade direction (long to short, short to long) and we can not calculate profit for this bar correctly
                            // Commenting this IF will result as a zero value on the bar on which the signal has happened
                            if ($add_bar_short && (count($short_trades) != 0))
                            {
                                //$profit_diagram [] = [$all_table_values[$element_index]->time_stamp, end($short_trades)[1] - $all_table_values[$element_index]->close];
                                $add_bar_short = false;

                                $accumulated_profit = $accumulated_profit + end($short_trades)[1] - $all_table_values[$element_index]->close;
                            }

                            $profit_diagram [] = [$all_table_values[$element_index]->time_stamp, $accumulated_profit + ($all_table_values[$element_index]->close - end($long_trades)[1])];

                        }

                        if ($position == "short") // If the short position is open
                        {
                            if ($add_bar_long && (count($long_trades) != 0)) // At the bar on which the position is closed - add a value to profit_diagram or otherwise it will be empty
                            {
                                //$profit_diagram [] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->close - end($long_trades)[1]];
                                $add_bar_long = false;

                                $accumulated_profit = $accumulated_profit + $all_table_values[$element_index]->close - end($long_trades)[1];
                            }

                            $profit_diagram [] = [$all_table_values[$element_index]->time_stamp, $accumulated_profit + (end($short_trades)[1] - $all_table_values[$element_index]->close)];
                        }

                    }

                }


                //$message[] = 'jopa'; // Variable for info messages
                $max_value = 0; // Reset to 0 the max value after loop through the interval
                $low_value = 999999; // Reset the low value


            }
            else // Add values to the array while max and low are not yet calculated. If values are added in this code - the quantity of values in $arr1[] and $arr2[] will be = $data[]
            {
                //$arr1[] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->high];
                //$message[] = 100;
            }

            $element_index++;
        }

        // Ending capital
        $ending_capital = $initial_capital + $accumulated_profit;
        $message [] = [$ending_capital];

        // Record $ending_capital to DB.
        $allTableValues1 = DB::table('assets')->get();

        // Loop through all found elements
        foreach ($allTableValues1 as $tableValue){

            DB::table('assets')
            ->where('asset_name', $tableValue->asset_name)
                ->update([
                    'ending_capital' => $ending_capital
                ]);

        }


        /*
        $extremes = array();
        $last = null;
        $num = count($array);
        for($i=0;$i<$num - 1;$i++) {
            $curr = $array[$i];
            if($last === null) {
                $extremes[] = $curr;
                $last = $curr;
                continue;
            }
        */


        $last  = null;
        $min = 99999999;
        $maxIndex = null;
        $max = null;
        $maxIndex = null;
        $num = count($profit_diagram); // Get quantity of bars in profit diagram

        for($i = 0; $i < $num - 1; $i++) {

            //$alert = $profit_diagram[2][1]; // [index][0,1   0 - date; 1 - value]

            $curr = $profit_diagram[$i][1]; // First value is datetime, second valur (double)
            //$alert = $curr;

            //break;

            if($last === null) { // Added first bar to the extremums array
                $extremes[] = [$profit_diagram[$i][0],$profit_diagram[$i][1]];
                $last = $curr;
                continue;
            }
            //min
            if($last > $curr && $curr < $profit_diagram[$i + 1][1]) {
                $extremes[] = [$profit_diagram[$i][0],$curr];
            }
            //max
            else if ($last < $curr && $curr > $profit_diagram[$i + 1][1]) {
                $extremes[] = [$profit_diagram[$i][0],$curr];
            }
            if($last != $curr && $curr != $profit_diagram[$i + 1][1]) {
                $last = $curr;
            }

        }

        //add last point
        $extremes[] = [$profit_diagram[$i][0],$profit_diagram[$num - 1][1]]; // Add found extrema to the array

        for ($z = 0; $z < count($extremes); $z++){

            if ($extremes[$z][1] > $max){
                $max = $extremes[$z][1];
                $maxIndex = $z;
            }

            if ($extremes[$z][1] < $min){
                $min = $extremes[$z][1];
                $minIndex = $z;
            }


        }





        // Drawdown calculation
        // We need to take the maximum of the array and subtract the lower value from it
        // Three cases are possible:
        // 1. Both values are positive. High - Low
        // 2. One is positive, other is negative. High - (-Low) (low has - sign)
        // 3. Both are negative. abs(high) - abs(low)

        /*
        $num = count($extremes); // Count all found extremas
        for($i=0; $i<$num; $i++) // Start from second element
        {
            if ($i > 0) { // Do not take first element. Otherwise we will no be able the take the previous element
                if ($extremes[$i - 1] > $extremes[$i]) { // We start from second element. If the previous is higher than current meaning that we are going down
                        if (max($extremes) > 0 && (min($extremes) >= 0)){
                            //$drawDawnVals [] = [1, max($extremes) - min($extremes)];
                            $localDrawDown = $extremes[$num - 1] - $extremes[$num]; // Previous - current
                        }

                        if (max($extremes) > 0 && (min($extremes) < 0)){
                            //$drawDawnVals [] = [2, max($extremes) - min($extremes)];
                            //$localDrawDown = max($extremes) - min($extremes);
                            $localDrawDown = $extremes[$i - 1] - $extremes[$i];
                        }

                        if (min($extremes) <= 0 && (max($extremes) < 0)){
                            //$drawDawnVals [] = [3, abs(min($extremes)) - max($extremes)];
                            //$localDrawDown = abs(min($extremes)) - max($extremes);
                            $localDrawDown = abs($extremes[$i]) - abs($extremes[$i - 1]);
                        }
                    }
            }

            if ($localDrawDown > $tempDrawDown) // Find the biggest value of drawdown
                $tempDrawDown = $localDrawDown;
        }

        $drawDawnVals [] = $tempDrawDown;
*/

        //$drawDawnVals [] = [abs(max($extremes)) - abs(min($extremes))];
        //$drawDawnVals [] = [5,77,88,123];
        //$drawDawnVals [] = $tempDrawDown;

        //$message [] = ["kopa"];
        //$arr2 = [1,9,11,2,5];

        //$drawDawnVals = $extremes;

        $extremesHigh [] = [$extremes[$maxIndex][0],$max];
        $extremesHigh [] = [$extremes[$minIndex][0],$min];


        if (Schema::hasTable('assets')) // If the table exists. If it does not - the historical data did not load due a error like "Too many requests"
        {
            //               0      1      2          3             4               5                 6                 7
            $seriesData = [$data, $arr1, $arr2, $long_trades, $short_trades, $profit_diagram, $accumulated_profit, $extremes, $extremesHigh]; // $data candles, $arr1 and $arr2 - upper and lower price channel. $message - the variable for transfering messages and other
            return $seriesData;

        }
        else
        {
            return (new \Illuminate\Http\Response)->setStatusCode(400, 'Error loading AJAX request. Table does not exists. LoadJsonFromDB.php');
        }



    }

}
