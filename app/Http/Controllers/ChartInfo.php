<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
/**
 * Class ChartInfo
 * Returns chart property values like Asset symbol, profit, commission vale etc.
 * This controller is called when start page with the chart loads.
 * Values are returned to ChartControl.vue
 * @package App\Http\Controllers
 */
class ChartInfo extends Controller
{
    public function load()
    {

        $response = array();
        $response = ChartInfo::arrayPush($response, 'symbol',
            DB::table('settings_realtime')
                ->where('id', 1)
                ->value('symbol'));

        /** @todo Add not null check. after table is truncated - getting a error. There is not net profit yet*/
        $response = ChartInfo::arrayPush($response, 'netProfit', "does not work!");
        /*
        $response = ChartInfo::arrayPush($response, 'netProfit',
            DB::table(env("ASSET_TABLE"))
                ->where('id', (DB::table(env("ASSET_TABLE"))->orderBy('time_stamp', 'desc')->first()->id))
                ->value('accumulated_profit'));
        */

        $netProfit =
            DB::table('asset_1')
                ->whereNotNull('net_profit')
                ->orderBy('id', 'desc')
                ->value('net_profit');

        $response = ChartInfo::arrayPush((array)DB::table('settings_realtime')->where('id', 1)->first(), 'netProfit', $netProfit);


        return
        $response;
        /*
            (array)DB::table('settings_realtime')
                ->where('id', 1)
                ->first();
        */
    }

    function arrayPush($array, $key, $value){
        $array[$key] = $value;
        return $array;
    }
}
