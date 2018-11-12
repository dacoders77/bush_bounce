<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

/*
 * This class resets all data in the DB.
 * Class is called from ChartControl.vue and when php artisan ratchet:start command is performed
 */
class initialstart extends Controller
{
    public function index(){

        // Set trade_flag to all. in the DB
        DB::table('settings_realtime')->where('id', 1)->update(['trade_flag' => 'all']); // First ever trade flag
        DB::table(env("ASSET_TABLE"))->truncate();
        DB::table(env("PROFIT_TABLE"))->truncate();
        DB::table('orders')->truncate();

        \App\Classes\History::load(); // Load history from www.bitfinex.com
        \App\Classes\PriceChannel::calculate(); // Calculate price channel for loaded data

        $messageArray['serverInitialStart'] = true;
        event(new \App\Events\BushBounce($messageArray)); // Event is received in Chart.vue

    }
}
