<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use View;

class priceChannel_controller extends Controller
{
    Public function index(Request $request){

        // Get all records from assets table
        $allTableValues1 = DB::table('assets')->get();

        // Loop through all found elements
        foreach ($allTableValues1 as $tableValue){

            DB::table('assets')
                ->where('asset_name', $tableValue->asset_name)
                ->update([
                    'price_channel_default_value' => $request->get('channel_period'),
                    'price_channel_start' => $request->get('channel_period_start'),
                    'price_channel_end' => $request->get('channel_period_end')
                ]);
        }


        //return redirect()->route('main.view');
        return View::make('master');


    }

}
