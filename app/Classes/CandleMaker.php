<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/29/2018
 * Time: 8:37 PM
 */

namespace App\Classes;
use App\Console\Commands\RatchetPawlSocket;
use Illuminate\Support\Facades\DB;
use Mockery\Exception;

/**
 * Class CandleMakers
 * Receives ticks from RatchetPawlSocket.php
 * Makes Candles and pass them to Chart.php
 * Also pass ticks to the front end and notifies the chart when a new bar is issued (via new bar issued flag)
 *
 * @return ??
 */

class CandleMaker
{
    private $symbol;
    private $tt; // Time
    private $timeFrame;
    private $barHigh = 0; // For high value calculation
    private $barLow = 9999999;
    private $settings;
    private $isFirstTickInBar;

    public function __construct()
    {
        $this->isFirstTickInBar = true;
        //$this->settings = DB::table('settings_realtime')->first(); // Removed to ratchet. Delete it
    }

    /**
     * Consumes price ticks and makes candles
     *
     * @return ??
     * where is it? tickTicker Ticker (BTCUSD, EYHUSD etc.)
     * @param double        $tickPrice The price of the current trade (tick)
     * @param date          $tickDate The date of the trade
     * @param double        $tickVolume The volume of the trade. Can be less than 1
     * @param collection    $settings Row of settings from DB
     * @param Command       $command Needed for throwing colored meddages to the console output (->info, ->error etc.)
     */
    public function index($tickPrice, $tickDate, $tickVolume, $chart, $settings, $command){

        echo "**********************************************CandleMaker.php<br>\n";

        //$chart = new Chart(); // Moved new instance creation to Ratchet class

        /** @todo remove this variable. use just $settings*/
        $this->settings = $settings;


        /** First time ever application run check. Table is empty */
        if(!DB::table('asset_1')->first())
        {
            echo "CandleMaker.php Application first ever run. Add first record to the table where OLHC = tick price\n";
            //History::load(); /** After the history is loaded - get price channel calculated */
            //PriceChannel::calculate(); // Calculate price channel
            DB::table('asset_1')->insert(array( // Record to DB
                'date' => gmdate("Y-m-d G:i:s", ($tickDate / 1000)), // Date in regular format. Converted from unix timestamp
                'time_stamp' => $tickDate,
                'open' => $tickPrice,
                'close' => $tickPrice,
                'high' => $tickPrice,
                'low' => $tickPrice,
                'volume' => $tickVolume,
            ));
        }


        /** Take seconds off and add 1 min. Do it only once per interval (for example 1min) */
        if ($this->isFirstTickInBar) {

            $x = date("Y-m-d H:i", $tickDate / 1000) . "\n"; // Take seconds off. Convert timestamp to date
            $this->tt = strtotime($x . $this->settings->time_frame . "minute");
            $this->isFirstTickInBar = false;
        }

        //echo "current time: " . date("Y-m-d H:i", $tickDate / 1000) . "\n";



        /** Calculate high and low of the bar then pass it to the chart in $messageArray */
        if ($tickPrice > $this->barHigh) // High
        {
            $this->barHigh = $tickPrice;
        }

        if ($tickPrice < $this->barLow) // Low
        {
            $this->barLow = $tickPrice;
        }

        try{
            $lastRecordId = DB::table('asset_1')->orderBy('time_stamp', 'desc')->first()->id;
        }
        catch(Exception $exception) {
            echo "CandleMaker.php. Get last id of the record error: " . $exception;
        }

        try {
            DB::table('asset_1')
                ->where('id', $lastRecordId) // id of the last record. desc - descent order
                ->update([
                    'close' => $tickPrice,
                    'high' => $this->barHigh,
                    'low' => $this->barLow,
                ]);
        }
        catch(Exception $e) {
            echo 'DB record update error: ' . $e->getMessage();
        }

        $command->error("current tick   : " . gmdate("Y-m-d G:i:s", ($tickDate / 1000)));
                   echo "time to comapre: " . gmdate("Y-m-d G:i:s", ($this->tt)) . "\n";
        echo "time frame: " . $this->settings->time_frame . "\n";

        //echo "************* tick: " . floor($tickDate / 1000) . "\n";
        //echo "************* tt: " . $this->tt . "\n";

        /*
         * New bar is issued
         * When the time of the tick is > added time - add this bar to the DB
         * @todo now volume is not accumulated. We record is the last volume of the trade
         */
        if (floor($tickDate / 1000) >= $this->tt){

            $command->info("------------------- NEW BAR ISSUED ----------------------");

            /** Send tick to Chart.php in order to calculate profit and position marks */
            //($mode, $barDate, $timeStamp, $barClosePrice, $id)
            $chart->index("history", gmdate("Y-m-d G:i:s", ($tickDate / 1000)), $tickDate, $tickPrice, null);

            /** Add bar to DB */
            DB::table('asset_1')->insert(array(
                'date' => gmdate("Y-m-d G:i:s", ($tickDate / 1000)), // Date in regular format. Converted from unix timestamp
                'time_stamp' => $tickDate,
                'open' => $tickPrice,
                'close' => $tickPrice,
                'high' => $tickPrice,
                'low' => $tickPrice,
                'volume' => $tickVolume,
            ));
                /**
                 * We get settings values from DB one more time just in case it was changed.
                 * For example the price channel value. Otherwise the price channel value will remain the same
                 * and the only option to update it would be restarting the application
                 */
                $this->settings = DB::table('settings_realtime')->first();

                /** Set flag to true in order to drop seconds of the time and add time frame */
                $this->isFirstTickInBar = true;

                /** Calculate price channel. All records in the DB are gonna be used
                 * @todo When bars are added, no need go through all bars and calculate price channel. We can go only through price channel perid bars and get the value. In this case PriceChannel class must have a parameter whether to calculate the whole data or just a period
                 */
                PriceChannel::calculate();

                /** This flag informs Chart.vue that it needs to add new bar to the chart.
                 * We reach this code only when new bar is issued and only in this case this flag is added.
                 * In all other cases $messageArray[] array does not contain flag ['flag'] which means that Chart.vue is
                 * not adding new bar and updating the current one
                 */
                $messageArray['flag'] = true;


            }


        /** Prepare message array */
        $messageArray['tradeDate'] = $tickDate;
        $messageArray['tradeVolume'] = $tickVolume;
        $messageArray['tradePrice'] = $tickPrice; // Tick price = current price and close (when a bar is closed)
        $messageArray['tradeBarHigh'] = $this->barHigh; // High value of the bar
        $messageArray['tradeBarLow'] = $this->barLow; // Low value of the bar

        /** Get price channel */
        $messageArray['priceChannelHighValue'] = (DB::table('asset_1')->orderBy('id', 'desc')->first())->price_channel_high_value;
        $messageArray['priceChannelLowValue'] = (DB::table('asset_1')->orderBy('id', 'desc')->first())->price_channel_low_value;

        /** Get trade and profit. All information is pulled from DB */
        // read last position
        // read last position profit
        // read last accumulated profit

        event(new \App\Events\BushBounce($messageArray)); // Event is received in Chart.vue



        /** Reset high, low of the bar but do not out send these values to the chart. Next bar will be started from scratch */
        if ($this->isFirstTickInBar == true){
            $this->barHigh = 0;
            $this->barLow = 9999999;
        }

    }
}