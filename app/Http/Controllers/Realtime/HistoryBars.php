<?php

namespace App\Http\Controllers\Realtime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Http;

/**
 * Class HistoryBars
 * Loads historical bars from DB and returns them to Chart.vue. Not from www.bitfinex.com
 * @package App\Http\Controllers\Realtime
 */
class HistoryBars extends \App\Http\Controllers\Controller
{
    public function load(){

        $candles = array();
        $priceChannelHighValues = array();
        $priceChannelLowValues = array();
        $longTradeMarkers = array();
        $shortTradeMarkers = array();

        $allDbValues = DB::table("asset_1")->get(); // Read the whole table from BD to $allDbValues

        foreach ($allDbValues as $rowValue) { // Go through all DB records

            $candles[] = [
                $rowValue->time_stamp,
                $rowValue->open,
                $rowValue->high,
                $rowValue->low,
                $rowValue->close,
            ];

            //$rowValue->price_channel_high_value,
            //$rowValue->price_channel_low_value

            $priceChannelHighValues[] = [
                $rowValue->time_stamp,
                $rowValue->price_channel_high_value
            ];

            $priceChannelLowValues[] = [
                $rowValue->time_stamp,
                $rowValue->price_channel_low_value
            ];

            // Add long trade markers
            if ($rowValue->trade_direction == "buy") {
                $longTradeMarkers[] = [
                    $rowValue->time_stamp,
                    $rowValue->trade_price
                ];
            }

            // Add short trade markers
            if ($rowValue->trade_direction == "sell") {
                $shortTradeMarkers[] = [
                    $rowValue->time_stamp,
                    $rowValue->trade_price
                ];
            }
        }

        $seriesData = array(
            "candles" => $candles,
            "priceChannelHighValues" => $priceChannelHighValues,
            "priceChannelLowValues" => $priceChannelLowValues,
            "longTradeMarkers" => $longTradeMarkers,
            "shortTradeMarkers" => $shortTradeMarkers
        );
        //              0                   1                       2                   3                   4
        //$seriesData = [$candles, $priceChannelHighValue, $priceChannelLowValue, $longTradeMarkers, $shortTradeMarkers];
        return json_encode($seriesData);
    }
}
