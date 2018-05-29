<?php

namespace App\Classes;
use GuzzleHttp\Client; // Guzzle is used to send http headers http://docs.guzzlephp.org/en/stable/
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;


/**
 * Class History
 * Gets history data from www.bitfinex.com and records in to the DB
 * @package App\Http\Controllers
 */

class History
{
    /**
     * Loads historical data to DB from www.bitfinex.com.
     * These bars are shown at the chart when the page is open.
     * Each bar has 5 values: 1526651340000,8095.7,8095.7,8090.1,8090.1
     * @return void
     */

    static public function load(){

        /**
         * If initial start is true
         * True is set by default or by Initial start button click at the start page
         * Is Set to false after history data is loaded
         */
        //if ((DB::table('settings_realtime')
        //        ->where('id', env("SETTING_ID"))
        //        ->value('initial_start'))){
        if (true){

            /*
            echo "request bars: " .
                DB::table('settings_realtime')
                    ->where('id', env("SETTING_ID"))
                    ->value('request_bars');
            */

            DB::table('asset_1') // Drop all records in the table
                ->truncate();

            $timeframe =
                DB::table('settings_realtime')
                    ->where('id', 1)
                    ->value('time_frame') . "m";

            $asset =
                DB::table('settings_realtime')
                    ->where('id', 1)
                    ->value('symbol');

            /**
             * Create guzzle http client
             * @var GuzzleHttp\Client $api_connection Provides http headers send functionality
             * @link http://docs.guzzlephp.org/en/stable/
             */
            $api_connection = new Client([
                'base_uri' => 'https://api.bitfinex.com/v2/',
                'timeout' => 50 // If make this value small - fatal error occurs
            ]);

            //$restEndpoint = "candles/trade:" . $timeframe . ":t" . $asset . "/hist?limit=20&start=" . $start . "&end=" . $end . "&sort=1";
            $restEndpoint = "candles/trade:" . $timeframe . ":t" . $asset . "/hist?limit=" . DB::table('settings_realtime')->where('id', 1)->value('request_bars'); // Gets bars from the present moment. No dates needed. Values must be reversed befor adding to DB. Otherwise - the chart is not properly rendered, all bars look fat

            // http://docs.guzzlephp.org/en/stable/request-options.html#http-errors
            $response = $api_connection->request('GET', $restEndpoint, ['http_errors' => true ]);

            //echo "GUZZLE reason: " . $response->getReasonPhrase() . "<br>";

            $body = $response->getBody(); // Get the body out of the request
            $json = json_decode($body, true); // Decode JSON. Associative array will be outputted

            if ($response->getStatusCode() == 200) // Request successful
            {
                /** Add candles to DB */
                foreach (array_reverse($json) as $z) { // The first element in array is the youngest - first from the left on the chart. Go through the array backwards. This is the order how points will be read from DB and outputed to the chart
                    DB::table('asset_1')->insert(array( // Record to DB
                        'date' => gmdate("Y-m-d G:i:s", ($z[0] / 1000)), // Date in regular format. Converted from unix timestamp
                        'time_stamp' => $z[0],
                        'open' => $z[1],
                        'close' => $z[2],
                        'high' => $z[3],
                        'low' => $z[4],
                        'volume' => $z[5],
                    ));
                }
            }
            else // Request is not successful. Error code is not 200
            {
                echo "History. Too many request error";
            }

            /** Ste Initial start flag to false */
            DB::table('settings_realtime')
                ->where('id', 1)
                ->update([
                    'initial_start' => 0,
                ]);
        }
    }
}
