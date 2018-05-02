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
        $accumulatedProfitValues = 0; // At the beggining = 0 and recorded when the trade is closd. The nex trade's profit starts from this value

        $trade_flag = "all";
        $profitDiagramValues = null; // We have to make it null. Otherwise in cases when there is no trades at the chart and thus no $profitDiagramValues is calculated - the error is thrown.

        $stopLossFlag = "all"; // Stop loss flag. Similar to long and short position. All state is for opening the first stop loss. Then it is high and low
        $position = null;
        $isFirstBarInTrade = true; // Count closed position on the same be the signal occurred. The problem is when the position is closed the close price of this bar goes to the next position
        $isFirstEverTrade = true; // First ever trade flag. Works onr time at the first trade on the chart then turns to false
        $shortTrades = [];
        $longTrades = [];


        // Variables for strategy testing parameters (results)
        $initial_capital = 0;

        $StartUpAsset =
            DB::table('assets')
                ->where('show_on_startup', 1)
                ->value('asset_name'); // We take only one asset from the DB. The one which has show_on_startup flag

        $price_channel_interval =
            DB::table('settings')
                ->where('id', 1)
                ->value('default_price_channel_period');

        $stopLossShift =
            DB::table('settings')
                ->where('id', 1)
                ->value('default_stop_loss_shift');

        $commissionValueDb =
            DB::table('settings')
                ->where('id', 1)
                ->value('commission_value');

        $all_table_values =
            DB::table('history_' . $StartUpAsset)->get(); // Read the whole table from BD to $all_table_values variable


        foreach ($all_table_values as $row_values) { // Go through all records (bars) loaded from the DB

            // Calculate commission value on each trade
            $commissionValue = $all_table_values[$element_index]->close * $commissionValueDb / 100;

            //echo  $commissionValue . "<br>";

            // Add a candle to the array. Main candlestick chart data. Put all values from the table to this array
            $chartBars[] = [$row_values->time_stamp, $row_values->open, $row_values->high, $row_values->low, $row_values->close];

            // Start from $price_channel_interval - 1 element. - 1 because elements in arrays are named from 0. We don't start calculating price channel from the first candle
            if ($element_index >= $price_channel_interval - 1)
            {
                // Cycle backwards through elements ($price_channel_interval) for calculating min and max
                for ($i = $element_index ; $i > $element_index - $price_channel_interval; $i--)
                {
                    if ($all_table_values[$i]->high > $max_value) // Find max value in interval
                        $max_value = $all_table_values[$i]->high;

                    if ($all_table_values[$i]->low < $low_value) // Find low value in interval
                        $low_value = $all_table_values[$i]->low;
                }

                // Price channel. Add found max value to the array
                $priceChannelHighValues[] = [$all_table_values[$element_index]->time_stamp, $max_value];
                $priceChannelLowValues[] = [$all_table_values[$element_index]->time_stamp, $low_value];

                // Stop loss price channel. Price channel + shift. Uncomment it to go back to regular stop loss
                //$stoplossChannelHighValues[] = [$all_table_values[$element_index]->time_stamp, $max_value + ($max_value - $low_value) * $stopLossShift / 100];
                //$stoplossChannelLowValues[] = [$all_table_values[$element_index]->time_stamp, $low_value - ($max_value - $low_value) * $stopLossShift / 100];


                // Fixed stop loss channel. Comment this code to get back to regular stop loss
                if ($element_index == $price_channel_interval - 1) // The first bar for which the price channel is calculated
                {
                    //echo "cc: " . gmdate("Y-m-d G:i:s", ($all_table_values[$element_index]->time_stamp / 1000)) . " fc: " . $trade_flag . "<br>";

                    $stopLossHighValue = $max_value + ($max_value - $low_value) * $stopLossShift / 100;
                    $stopLossLowValue = $low_value - ($max_value - $low_value) * $stopLossShift / 100;
                }




                // TRADES
                // Trades testing. Adding trades to $longTrades[] and $shortTrades[] for output to the chart
                if ($element_index >= $price_channel_interval) // Start from the next element after which the high value(price channel) has been calculated
                {
                    // SHORT
                    if ($all_table_values[$element_index]->close > $priceChannelHighValues[$element_index - $price_channel_interval][1]
                        && ($trade_flag == "all" || $trade_flag == "long")) {
                        $shortTrades[] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->close]; // Added long marker
                        $trade_flag = "short";
                        $position = "short";
                        $isFirstBarInTrade = true;
                        $stopLossFlag = "all"; // Reset stop loss flag

                        // Calculation for fixed stop loss channel. Comment it to get back to regular stop loss
                        $stopLossHighValue = $max_value + ($max_value - $low_value) * $stopLossShift / 100;
                    }
                    // LONG
                    if ($all_table_values[$element_index]->close < $priceChannelLowValues[$element_index - $price_channel_interval][1]
                        && ($trade_flag == "all"  || $trade_flag == "short")) {
                        $longTrades[] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->close]; // Added short marker
                        $trade_flag = "long";
                        $position = "long";
                        $isFirstBarInTrade = true;
                        $stopLossFlag = "all"; // Reset stop loss flag

                        // Calculation for fixed stop loss channel. Comment it to get back to regular stop loss
                        $stopLossLowValue = $low_value - ($max_value - $low_value) * $stopLossShift / 100;
                    }

                    // Fixed stop loss calculation. Comment it to get back to regular
                    $stoplossChannelHighValues[] = [$all_table_values[$element_index]->time_stamp, $stopLossHighValue];
                    $stoplossChannelLowValues[] = [$all_table_values[$element_index]->time_stamp, $stopLossLowValue];


                    // STOP LOSS

                    // For short
                    if ($all_table_values[$element_index]->close > $stoplossChannelHighValues[$element_index - $price_channel_interval][1]
                        && $position == "short"
                        && end($shortTrades)[0] != $all_table_values[$element_index]->time_stamp // Exclude stop loss at the bar when a trade was executed
                        && ($stopLossFlag == "all" || $stopLossFlag == "low")) // All - a condition for getting into this IF when the first ever stop loss occured
                    {
                        $stopLossHighValues[] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->close]; // Added stop loss marker
                        $stopLossFlag = "high";
                    }

                    // For long
                    if ($all_table_values[$element_index]->close < $stoplossChannelLowValues[$element_index - $price_channel_interval][1]
                        && $position == "long"
                        && end($longTrades)[0] != $all_table_values[$element_index]->time_stamp
                        && ($stopLossFlag == "all" || $stopLossFlag == "high")) {
                        $stopLossLowValues[] = [$all_table_values[$element_index]->time_stamp, $all_table_values[$element_index]->close]; // Added short marker
                        $stopLossFlag = "low";
                    }


                    // PROFIT CALCULATION

                    // Find the presence of a stop loss between current bar and the previous trade (long or short)
                    // stop loss flag?
                    $stopLossStateTime = gmdate("Y-m-d G:i:s", ($all_table_values[$element_index]->time_stamp / 1000));
                    $stopLossStateValues[] = [$stopLossStateTime, $stopLossFlag];

                    // Start calculating profit values only after the first trade. $shortTrades or $longTrades array are not empty
                    if (count($longTrades) || count($shortTrades))
                    {
                        if ($position == "long") // If the long position is open. $position
                        {
                            // Go backwards through the array of stop loss sates. We need to determine how the the previous trade was closed. With a stop loss or not
                            foreach (array_reverse($stopLossStateValues) as $stopLossStateValue){
                                if ($stopLossStateValue[1] == "all") // Regular exit or first enter. Checked at the currents bar
                                {
                                    if ($isFirstEverTrade) // first ever trade
                                    {
                                        //echo "first ever: " . gmdate("Y-m-d G:i:s", ($all_table_values[$element_index]->time_stamp / 1000)) . " fc: " . $isFirstBarInTrade . "<br>";
                                        $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, -$commissionValue]; // On the first ever trade we dont have any profit yet but the comission is already payed
                                    }
                                    if (!$isFirstEverTrade) // All trades except first ever
                                    {
                                        //echo "cc: " . gmdate("Y-m-d G:i:s", ($all_table_values[$element_index]->time_stamp / 1000)) . " fc: " . $isFirstBarInTrade . "<br>";
                                        if ($isFirstBarInTrade) // First bar in the trade and not the first trade ever
                                        {
                                            // previous trade was closed without a stop loss
                                            if ($stopLossStateValues[count($stopLossStateValues)-2][1] == "all")
                                            {
                                                $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] + ($all_table_values[$element_index - 1]->close - $all_table_values[$element_index]->close) - $commissionValue];
                                            }
                                            // previous trade was closed with a stop loss
                                            if ($stopLossStateValues[count($stopLossStateValues)-2][1] == "high")
                                            {
                                                $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] - $commissionValue];
                                            }
                                        }
                                        else // All bars in the trade except first one
                                        {
                                            //echo "cccc: " . gmdate("Y-m-d G:i:s", ($all_table_values[$element_index]->time_stamp / 1000)) . " fc: " . $isFirstBarInTrade . "<br>";
                                            $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] + ($all_table_values[$element_index]->close - $all_table_values[$element_index - 1]->close)];
                                        }
                                    }
                                    $isFirstBarInTrade = false;
                                    $isFirstEverTrade = false;
                                    break; // When the first match found - break the loop
                                }
                                if ($stopLossStateValue[1] == "low") // Stop loss exit
                                {
                                    // If the previous state of the stop loss is "all" - it means that this is the bar on which stop loss has occurred. We need to calculate profit for this bar
                                    if (($stopLossStateValues[count($stopLossStateValues)-2][1] == "all"))
                                    {
                                        $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] + $all_table_values[$element_index]->close - $all_table_values[$element_index - 1]->close];
                                    }
                                    else // When the state is not "ALL" (in this case it is "low" because low stop loss has just happened) it means that we are on the second bar after the stop loss and we need just to copy the profit on each bar
                                    {
                                        $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1]];
                                    }
                                    break;
                                }
                            }
                            // When the position is changed, on the bar on which the signal happened (close > max), we need to proceed this last bar with the same trade direction.
                            // When the signal happens this bar is already in the opposite trade direction (long to short, short to long) and we can not calculate profit for this bar correctly
                            // Commenting this IF will result as a zero value on the bar on which the signal has occurred
                        }
                    }

                        if ($position == "short") // If short position is open and at least one long trade was open
                        {
                            foreach (array_reverse($stopLossStateValues) as $stopLossStateValue){

                                if ($stopLossStateValue[1] == "all")
                                {
                                    if ($isFirstEverTrade) // first ever trade at the chart
                                    {
                                        $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, -$commissionValue];
                                    }
                                    if (!$isFirstEverTrade) // All trades except first ever
                                    {
                                        if ($isFirstBarInTrade) // First bar in the trade
                                        {
                                            if ($stopLossStateValues[count($stopLossStateValues)-2][1] == "all") // Closed without a stop loss
                                            {
                                                $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] + ($all_table_values[$element_index]->close - $all_table_values[$element_index - 1]->close) - $commissionValue];
                                            }
                                            if ($stopLossStateValues[count($stopLossStateValues)-2][1] == "low") // If closed with stop loss
                                            {
                                                $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] - $commissionValue];
                                            }
                                        }
                                        else
                                        {
                                            $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] + ($all_table_values[$element_index - 1]->close - $all_table_values[$element_index]->close)];
                                        }
                                    }

                                    $isFirstBarInTrade = false;
                                    $isFirstEverTrade = false;
                                    break; // When the first match found - break the loop
                                }

                                if ($stopLossStateValue[1] == "high") // Stop loss exit
                                {
                                    if (($stopLossStateValues[count($stopLossStateValues)-2][1] == "all")) // Previous stop loss state is "all"
                                    {

                                        //echo "zzz: " . $stopLossStateValue[0] . " " . $stopLossStateValue[1] . "<br>";
                                        $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1] + $all_table_values[$element_index - 1]->close - $all_table_values[$element_index]->close];
                                    }
                                    else // is "high"
                                    {

                                        $profitDiagramValues [] = [$all_table_values[$element_index]->time_stamp, end($profitDiagramValues)[1]];
                                    }
                                    break;
                                }
                            }
                        }
                }
                $max_value = 0; // Reset to 0 the max value after loop through the interval
                $low_value = 999999; // Reset the low value
            }
            $element_index++;
        }

        // Ending capital
        $ending_capital = $initial_capital + $accumulatedProfitValues;
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

        $last  = null;
        $min = 99999999;
        $maxIndex = null;
        $max = null;
        $maxIndex = null;

        $accumulatedProfitValues = last($profitDiagramValues)[1]; // Last value of the the profit diagram - is resulted financial result (ending capital)

        if (Schema::hasTable('assets')) // If the table exists. If it does not - the historical data did not load due a error like "Too many requests"
        {
            $chartVariables = [
                $chartBars, // 0 Chart
                $priceChannelHighValues, // 1 Price channel high
                $priceChannelLowValues, // 2 Price channel low
                $longTrades, // 3 Long trades markers
                $shortTrades, // 4 Short trades markers
                $profitDiagramValues, // 5
                $accumulatedProfitValues, // 6
                $stoplossChannelHighValues, // 7 Stopp loss high channel
                $stoplossChannelLowValues, // 8 Stopp loss high channel
                $stopLossHighValues, // 9 Stop loss high markers
                $stopLossLowValues, // 10 Stop loss low markers
                $message // 11 Info message
            ]; // $chartBars candles, $priceChannelHighValues and $priceChannelLowValues - upper and lower price channel. $message - the variable for transfering messages and other

            return $chartVariables;
        }
        else
        {
            return (new \Illuminate\Http\Response)->setStatusCode(400, 'Error loading AJAX request. Table does not exists. LoadJsonFromDB.php');
        }

    }

}
