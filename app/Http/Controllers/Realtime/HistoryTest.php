<?php

namespace App\Http\Controllers\Realtime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Http;
use GuzzleHttp\Client; // Guzzle is used to send http headers http://docs.guzzlephp.org/en/stable/



class HistoryTest extends \App\Http\Controllers\Controller
{
    public function index(int $param){

        if ($param == 1) // Load history data from www.bitfinex.com
        {
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

        if ($param == 2) // Broadcast messages to Chart.php
        {
            echo "broadcast: <br>";
            $message = array(
                0 => null,
                1 => null,
                2 => [
                    0 => null,
                    1 => 43, // timestamp
                    2 => 43, // vol
                    3 => 43 //price
                ]

            );

            //dump($message);


            $allDbValues = DB::table("asset_1")->get(); // Read the whole table from BD to $allDbValues
            foreach ($allDbValues as $rowValue) { // Go through all DB records

                $message = array(
                    0 => null,
                    1 => null,
                    2 => [
                        0 => null,
                        1 => $rowValue->time_stamp, // timestamp
                        2 => null, // vol
                        3 => $rowValue->close //price
                    ]
                );

                dd($message);
            }

        }

        if ($param == 3) // Broadcast messages to Chart.php
        {
            DB::table('asset_1')->truncate(); // Truncate
            echo "table truncated";

        }






    }
}
