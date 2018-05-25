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
 * Updates price channel value and DB and then recalculates price channel via PriceChannel::calculate() method call
 * @package App\Http\Controllers\Realtime
 */
class ChartControl extends \App\Http\Controllers\Controller
{
    public function update(Request $request){

        DB::table('settings_realtime')
            ->where('id', 1)
            ->update([
                'price_channel_period' => $request->get('priceChannelPeriod')
                //'execution_time' => date("Y-m-d G:i:s", strtotime($request->get('basketExecTime')))
            ]);

        // Calculate price channel
        //\App\Http\Controllers\Realtime\PriceChannel::calculate();
        //PriceChannel::calculate();

        //return json_encode($seriesData);
    }
}
