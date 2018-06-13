<?php

namespace App\Classes;
use Illuminate\Support\Facades\DB;

/**
 * Class Backtest
 * This class takes historical bars loaded from www.bitfinex.com one by one
 * and calculates profit. Calculated profit, positions, accumulated profit are recorded to DB.
 * This class simulates real ticks coming from the exchange. In this case only one tick per bar will be generated - close.
 *
 * @package App\Classes
 */
class Backtest
{
    static public function start(){

        /** Empty calculated data like position, profit, accumulated profit etc */
        DB::table("asset_1")
            //->whereNotNull('net_profit')
            ->whereNotNull('price_channel_high_value')
            ->update([
                'trade_date' => null,
                'trade_price' => null,
                'trade_commission' => null,
                'accumulated_commission' => null,
                'trade_direction' => null,
                'trade_volume' => null,
                'trade_profit' => null,
                'accumulated_profit' => null,
                'net_profit' => null,
            ]);

        $chart = new Chart();

        $allDbValues = DB::table("asset_1")
            ->whereNotNull('price_channel_high_value')
            ->get(); // Read the whole table from BD to $allDbValues

        $isFirstRecord = false;
        foreach ($allDbValues as $rowValue) { // Go through all DB records

            /** We need to pass the first bar. It is needed to avoid null price channel trade check because
             * in Chart.php the penultimate value of the price channel is taken for calculation
             * for the first iteration of foreach this value is always null
             */
            if ($isFirstRecord){
                $chart->index("backtest", $rowValue->date, $rowValue->time_stamp, $rowValue->close, $rowValue->id);
            }
            else{
                $isFirstRecord = true;
            }
        }
    }
}