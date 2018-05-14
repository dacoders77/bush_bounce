<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use View;

class priceChannel_controller extends Controller
{
    Public function index(Request $request){

        // Get all records from assets table
        // $allTableValues1 = DB::table('assets')->get();

        // Loop through all assets and update values
        // foreach ($allTableValues1 as $tableValue){

            DB::table('settings_tester')
                ->where('id', '1')
                ->update([
                    'default_price_channel_period' => $request->get('channel_period'),
                    'default_stop_loss_shift' => $request->get('stop_loss_shift'),
                ]);
        //}
        return View::make('master');
    }
}
