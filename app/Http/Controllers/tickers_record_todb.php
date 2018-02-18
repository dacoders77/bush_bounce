<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class tickers_record_todb extends Controller
{
    /**
     * tickers_record_todb class does:
     * 1. Clears assets table (all records will be deleted)
     * 2. Take all assets names marked in the form ticker.blade.php with check boxes and insert their names to assets table
     * 2.1 For the only one asset marked with radio button show_on_startup filed is updated with 1. Later this asset will appear on the page when it is loaded
     * 3. Go through all newly inserted records and and add start, end and time frame values also read from $request ($request contains all fields sent from the form)
     * 4.
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        //echo "date " . DB::table('assets')->where('id', 1)->value('load_history_start');
        //echo "<br>";

        DB::table('assets')->truncate(); // Drop all records in the table

        $input = $request->all(); // Get all fileds of the form

        foreach ($input as $x => $x_value)
        {
            if ($x != "_token" && $x != "radio" && $x != "radio1" && $x != "start" && $x != "end") // There are token and radio variables in the request sent from the form. Take all variable except these once
            {
                // Added asset names market in the form with checkbox to assets table
                DB::table('assets')->insert(array(
                    'asset_name' => $x,
                    'show_on_startup' => 0,
                    'price_channel_default_value' => 10,
                    'price_channel_start' => 5,
                    'price_channel_end' => 24,
                ));
            }

            if ($x == "radio") // The asset marked with radio button - is the one which is shown at the chart page on start up
            {

                DB::table('assets')
                    ->where('asset_name', $x_value)
                    ->update([
                        'show_on_startup' => 1
                    ]); // works good
            }

            if ($x == "start") // Get start date from the input
            {
                $start = $x_value;
            }

            if ($x == "end")
            {
                $end = $x_value;
            }

            if ($x == "radio1")
            {
                $time_frame = $x_value;
            }
        }

        $all_table_values = DB::table('assets')->get(); // Read the whole table from BD to $all_table_values variable

        foreach ($all_table_values as $row_value) // For all assets listed in assets table update start, end and time_trame fields. All records fill have the same values
        {
            echo "************start update. " . $start . " asset: " . $row_value->asset_name;
            echo "<br>";

            DB::table('assets')
               ->where('asset_name', $row_value->asset_name)
               ->update([
                   'load_history_start' => $start,
                   'load_history_end' => $end,
                   'timeframe' => $time_frame
               ]); // works good
        }

        return redirect()->route('history.get');
        //return redirect()->action('history_finex@index');
    }
}
