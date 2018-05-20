<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * Returns chart property values like Asset symbol, profit, commission vale etc.
 * This controller is called when start page with the chart loads
 * Class ChartInfo
 * @package App\Http\Controllers
 */
class ChartInfo extends Controller
{
    public function load()
    {
        /*
        Asset name
        Net profit (updates in realtime)
        Requested historical bars
        Commission value
        Trading allowed flag
        */

        $response = array();
        $response = ChartInfo::arrayPush($response, 'symbol',
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('symbol'));

        /** @todo Add not null check. after table is truncated - getting the error. There is not net profit yet*/
        $response = ChartInfo::arrayPush($response, 'netProfit', "does not work!");
        /*
        $response = ChartInfo::arrayPush($response, 'netProfit',
            DB::table(env("ASSET_TABLE"))
                ->where('id', (DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id))
                ->value('accumulated_profit'));
        */

        $response = ChartInfo::arrayPush($response, 'requestedBars',
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('request_bars'));

        $response = ChartInfo::arrayPush($response, 'commissionValue',
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('commission_value'));

        $response = ChartInfo::arrayPush($response, 'tradingAllowed',
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('allow_trading'));

        //dump ($response);

        return json_encode($response);
    }

    function arrayPush($array, $key, $value){
        $array[$key] = $value;
        return $array;
    }
}
