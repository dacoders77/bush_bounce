<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use GuzzleHttp\Client; // Guzzle is used to send http headers
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;


/*
 * Candles data retrieved from www.bitfines.com
 * Unauthenticated channel and API v2
 * https://docs.bitfinex.com/v2/reference#rest-public-candles
 *
 * 1. Read the list of assets from assets table (DB)
 * 1.1 Truncate created_tables_for_history_data (drop all created table records in DB)
 * 2. Create tables for received assets using the same names
 * 3. Add created table to created_tables_for_history_data in DB
 */

class history_finex extends Controller
{
    /**
     * The class request the historical data from www.bitfinex.com
     * The assets for which the historical data should be requested are stored in assets table in DB as well as start, end dates,
     * time frame and startup asset (shown when the page is loaded)
     *
     * Reads all assets from assets table and create tables named history_asset_name (history_btcusd)
     * While creating history_asset_name tabele the asset name is inserten into created_tables_for_history_data.
     * Then loop through all assets in created_tables_for_history_data and retrieve history data.
     * created_tables_for_history_data table is used for dropping history_asset_name tables from DB while making a new dataset request
     *
     * Returns main view
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
        

    Public function index(Request $request)
    {

        /** @var \stdClass $allTableValues Get all records from tables_for_history_data */
        $allTableValues = DB::table('created_tables_for_history_data')->get();

        foreach ($allTableValues as $tableValue){
            // Loop through acquired records
            // Drop the table (with history data)
            Schema::drop("history_" . $tableValue->history_asset_name);  // Schema::dropIfExists()
            echo "###########################table dropped: " . $tableValue->history_asset_name;
            echo "<br>";
        }

        // Truncate created_tables_for_history_data (delete all records)
        DB::table('created_tables_for_history_data')->truncate(); // Drop all records in the table

        // Get all records from assets table
        $allTableValues1 = DB::table('assets')->get();

        // Loop through all elements
        foreach ($allTableValues1 as $tableValue){

            // Drop the table if it exists. In some cases when the code execution is interrupted - we need delete these tables. Otherwise - error is thrown
            Schema::dropIfExists("history_" . $tableValue->asset_name);  // Schema::dropIfExists()

            // Create the table in the DB with the same name: history_btcusd, history_ethusd etc. The historical data will be loaded into these tables
            Schema::create("history_" . $tableValue->asset_name , function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('date')->nullable(); // Use nullable if this field can be blank
                $table->bigInteger('time_stamp')->nullable();
                $table->float('open')->nullable();
                $table->float('close')->nullable();
                $table->float('high')->nullable();
                $table->float('low')->nullable();
                $table->float('volume')->nullable();
            });
            echo "table created: " . $tableValue->asset_name;
            echo "<br>";

            // Insert created table names to created_tables_for_history_data (using given names).
            // We need to have the list of all created tables. These names are used for deleting the corespondent tables
            // when the new historical data request is sent from the tickers.blade.php form
            //
            DB::table('created_tables_for_history_data')->insert(array(
                'history_asset_name' => $tableValue->asset_name // Date in regular format. Converted from unix timestamp
            ));
        }



        // Request history data for each asset
        // Fill history_ tables with received historical data




        // Validation rules
        //$this->validate($request,[
        //    'start' => 'required',
        //    'end' => 'required|date'
        //]);

        //$start = (strtotime($request->get('start')) * 1000);
        //$end = (strtotime($request->get('end')) * 1000);

        // Read start and end dates, timeframe from the DB. For this pero
        $start = (strtotime(DB::table('assets')->where('id', 1)->value('load_history_start')) * 1000); // Timestamp
        $end = (strtotime(DB::table('assets')->where('id', 1)->value('load_history_end')) * 1000);
        $timeframe = DB::table('assets')->where('id', 1)->value('timeframe');



        echo "start: " . gmdate ("d-m-Y G:i:s", ($start / 1000)) . " end: " . gmdate ("d-m-Y G:i:s", ($end / 1000)) . "<br>";
        echo "timeframe: " . $timeframe . "<br>";


        // Loop through all elements is assets
        foreach ($allTableValues1 as $tableValue) {

            echo "<br>";
            echo "                                             current asset: " . $tableValue->asset_name;

            // Create guzzle http client
            $api_connection = new Client([
                'base_uri' => 'https://api.bitfinex.com/v2/',
                'timeout' => 50 // If make this value small - fatal error occurs
            ]);

            // working end point! do not touch it!
            //$restEndpoint = "candles/trade:" . $timeframe . ":tBTCUSD/hist?limit=1000&start=" . $start . "&end=" . $end . "&sort=1";

            DB::table('history_' . $tableValue->asset_name)->truncate(); // Drop all records in the table




            // Break requested time period into pieces and request one by one

            $q = 1;
            // READING THIS VARS AGAIN! FIX IT! there is another reading at the beggining of the code
            // Read star and end date, timeframe from the DB
            $start = (strtotime(DB::table('assets')->where('id', 1)->value('load_history_start')) * 1000); // Timestamp
            $end = (strtotime(DB::table('assets')->where('id', 1)->value('load_history_end')) * 1000);

            $tempEnd = $end;
            $dayStep = 1; // Previous value 9. 1 for one day history loading

            while ($tempEnd <= $end){

                echo "<br><br>" . $q . " step: ";
                $tempEnd = $start + 86400000 * $dayStep;
                echo "GAP: " . gmdate ("d-m-Y G:i:s", ($start / 1000)) . " - " . gmdate ("d-m-Y G:i:s", ($tempEnd / 1000)) . "<br>";




            // delete }

            // delete for ($x = 0; $x <= 40; $x += 10) // 3 steps. 20 days each. works fine for 1h timeframe
            // delete {

                echo "<br>***************************************************Step в„– " . $q;

                // Time fromat: YYYY-MM-DD
                //$restEndpoint = "candles/trade:" . $timeframe . ":t" . strtoupper($tableValue->asset_name) . "/hist?limit=1000&start=" . (strtotime("2017-01-01 +0 day") * 1000) . "&end=" . (strtotime("2017-01-05 +0 day") * 1000) . "&sort=1"; //
                $restEndpoint = "candles/trade:" . $timeframe . ":t" . strtoupper($tableValue->asset_name) . "/hist?limit=1000&start=" . $start . "&end=" . $tempEnd . "&sort=1"; //

                // Create request and assign its result to $response variable
                // $response = $api_connection->request('GET', $restEndpoint, ['headers' => [] ]); // http://docs.guzzlephp.org/en/stable/request-options.html#http-errors
                $response = $api_connection->request('GET', $restEndpoint, ['http_errors' => false ]);

                //echo "<br>GUZZLE ERROR!: ________________________ " . $response->getStatusCode() . "<br>";
                echo "<br>GUZZLE reason: " . $response->getReasonPhrase() . "<br>";

                $body = $response->getBody(); // Get the body out of the request

                $json = json_decode($body, true);

                if ($response->getStatusCode() == 200) // Request successful
                {

                    $i = 1;
                    foreach ($json as $z) {

                        //echo '<pre>';
                        //echo $z[0] . " ";
                        //echo $i . " " . gmdate("d-m-Y G:i:s", ($z[0] / 1000));
                        //echo '</pre>';
                        //$i++;

                        DB::table('history_' . $tableValue->asset_name)->insert(array(
                            'date' => gmdate("Y-m-d G:i:s", ($z[0] / 1000)), // Date in regular format. Converted from unix timestamp
                            'time_stamp' => $z[0],
                            'open' => $z[1],
                            'close' => $z[2],
                            'high' => $z[3],
                            'low' => $z[4],
                            'volume' => $z[5],

                        ));

                    } // foreach

                    // https://www.youtube.com/watch?v=6-5XrSvjsic
                    //session()->flash('notif', 'The historical data loaded successfully! ' . $i . ' candles in total. Last loaded date: ' . gmdate("Y-m-d G:i:s", ($z[0] / 1000))); // Add flash message to the session. Later it will be read and displayed from the session
                    session()->flash('notif', 'The historical data loaded successfully! ' . $i . ' candles in total. Last loaded date: ');

                } // if 200

                else // Request is not successful. Error code is not 200

                {
                    echo "<script>alert('Request error: too many requests!' )</script>"; // $response->getReasonPhrase()
                }

                $start = $start + 86400000 * $dayStep; // 10 days step. 86400000 millesecs - 1 day
                $tempEnd = $tempEnd + 86400000 * $dayStep;
                $q++;

            } // while


        } // foreach


        return redirect()->route('main.view');

    }


}
