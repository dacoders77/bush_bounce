<?php

namespace App\Http\Controllers\Realtime;
use App\Http\Controllers\priceChannel_controller;
use ConsoleTVs\Charts\Builder\Realtime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Http;

/**
 * Class ChartControl
 * Updates price channel value and DB and then recalculates price channel via PriceChannel::calculate() method call.
 * This class is called from ChartControl.vue when app mode is changed from history to real-time and back
 * @package App\Http\Controllers\Realtime
 */
class ChartControl extends \App\Http\Controllers\Controller
{
    public function update(Request $request){
        DB::table('settings_realtime')
            ->where('id', 1)
            ->update([
                'price_channel_period' => $request->get('priceChannelPeriod'),
                'time_frame' => $request->get('timeFrame'),
                'request_bars' => $request->get('requestBars'),
                'symbol' => $request->get('symbol'),
                'history_from' => $request->get('historyFrom'),
                'history_to' => $request->get('historyTo'),
                'app_mode' => $request->get('appMode')
                //'execution_time' => date("Y-m-d G:i:s", strtotime($request->get('basketExecTime')))
            ]);

        // Calculate price channel
        \App\Classes\PriceChannel::calculate();
    }
}
