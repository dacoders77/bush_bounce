<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 5/18/2018
 * Time: 3:15 PM
 */

namespace App\Classes;
use Illuminate\Support\Facades\DB;

/**
 * Class PriceChannel calculates price channel high and low values based on data read from DB loaded from www.bitfinex.com.
 * Values are recorded (updated) in DB when calculated.
 * This class is called in 3 cases:
 * 1. On the first start of the application when the DB is empty and contains no historical data.
 * 2. When a new bar is issued
 * 3. When Initial start button is clicked from GUI in ChartControl.vue
 *
 * @package App\Classes
 * @return void
 */
class PriceChannel
{
    public static function calculate()
    {
        /** @var int $priceChannelPeriod */
        $priceChannelPeriod = DB::table('settings_realtime')
            ->where('id', 1)
            ->value('price_channel_period');

        /**
         * @var int elementIndex Loop index. If the price channel period is 5 the loop will go from 0 to 4.
         * The loop is started on each candle while running through all candles in the array.
         */
        $elementIndex = 0;

        /** @var int $priceChannelHighValue Base value for high value search*/
        $priceChannelHighValue = 0;

        /** @var int $priceChannelLowValue Base value for low value search. Really big value is needed at the beginning.
        Then we compare current value with 999999. It is, $priceChannelLowValue = current value*/
        $priceChannelLowValue = 999999;

        /**
         * desc - from big values to small. asc - from small to big
         * in this case: desc. [0] element is the last record in DB. and it's id - quantity of records
         * @var json object $records Contains all DB data (records) in json format
         * IT IS NOT A JSON! IT MOST LIKLEY LARAVEL OBJECT. BUTSCH WATED TO SEND ME THE LINK
         * https://laravel.com/docs/5.6/collections
         */
        $records = DB::table("asset_1")
            ->orderBy('time_stamp', 'desc')
            ->get(); // desc, asc - order. Read the whole table from BD to $records

        /** @var int $quantityOfBars The quantity of bars for which the price channel will be calculated */
        $quantityOfBars = (DB::table('asset_1')
            ->orderBy('id', 'desc')
            ->first())->id - $priceChannelPeriod - 1;

        /**
         * Calculate price channel max, min
         * First element in the array is the oldest
         * Start from the oldest element in the array which is on the right at the chart. The one on the left at the chart
         */
        foreach ($records as $record) {
            /**
             * Indexex go like this 0,1,2,3,4,5,6 from left to the right
             * We must stop before $requestBars reaches the end of the array
             */
            if ($elementIndex <= $quantityOfBars)
            {
                // Go from right to left
                for ($i = $elementIndex ; $i < $elementIndex + $priceChannelPeriod; $i++)
                {
                    echo "---------------$i for: " . $records[$i]->date . "<br>";

                    /** Find max value in interval */
                    if ($records[$i]->high > $priceChannelHighValue)
                        $priceChannelHighValue = $records[$i]->high;
                    /** Find low value in interval */
                    if ($records[$i]->low < $priceChannelLowValue)
                        $priceChannelLowValue = $records[$i]->low;
                }

                echo "$elementIndex " . $records[$elementIndex]->date . " " . $priceChannelHighValue . "<br>";

                /** Update high and low values in DB */
                DB::table("asset_1")
                    ->where('time_stamp', $records[$elementIndex]->time_stamp)
                    ->update([
                        'price_channel_high_value' => $priceChannelHighValue,
                        'price_channel_low_value' => $priceChannelLowValue,
                    ]);

                /** Reset high, low price channel values */
                $priceChannelHighValue = 0;
                $priceChannelLowValue = 999999;
            }
            $elementIndex++;
        }
    }
}