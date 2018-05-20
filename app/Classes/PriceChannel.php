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
 * Class PriceChannel calculates price channel high and low values based on data read from DB loaded from www.bitfinex.com
 * Values are recorded (updated) in DB when calculated.
 * This class is called in 3 cases:
 * 1. On the first (initial) start of the application when the DB is empty and contains no historical data
 * 2. When a new bar is issued
 * 3. When Initial start button is clicked from GUI
 *
 * @package App\Classes
 * @return void
 */
class PriceChannel
{
    public static function calculate()
    {
        /** @var int $priceChannelPeriod */
        $priceChannelPeriod = DB::table('settings')
            ->where('id', env("SETTING_ID"))
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
        echo "IndicatorPriceChannel.php Indicator recalculation started\n";
        /**
         * desc - from big values to small. asc - from small to big
         * in this case: desc. [0] element is the last record in DB. and it's id - quantity of records
         * @var json object $allDbRows Contains all DB data in json format*/
        $allDbRows = DB::table(env("ASSET_TABLE"))
            ->orderBy('time_stamp', 'desc')
            ->get(); // desc, asc - order. Read the whole table from BD to $allDbRows

        /**
         * Calculate price channel max, min
         * First element in the array is the oldest
         *
         * // Start from the oldest element in the array. The one on the left at the chart
         */
        foreach ($allDbRows as $z) {
            /** We must stop before $requestBars reaches the end of the array */
            if ($elementIndex <=
                DB::table('settings')
                    ->where('id', env("SETTING_ID"))
                    ->value('request_bars') - $priceChannelPeriod - 1)
            {
                for ($i = $elementIndex ; $i < $elementIndex + $priceChannelPeriod; $i++)
                {
                    if ($allDbRows[$i]->high > $priceChannelHighValue) // Find max value in interval
                        $priceChannelHighValue = $allDbRows[$i]->high;

                    if ($allDbRows[$i]->low < $priceChannelLowValue) // Find low value in interval
                        $priceChannelLowValue = $allDbRows[$i]->low;
                }

                /** Update high and low values */
                DB::table(env("ASSET_TABLE"))
                    ->where('time_stamp', $allDbRows[$elementIndex]->time_stamp)
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