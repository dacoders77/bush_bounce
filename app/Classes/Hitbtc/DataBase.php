<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 10/21/2018
 * Time: 3:12 AM
 */

namespace App\Classes\Hitbtc;
use Illuminate\Support\Facades\DB;

/**
 * Class DataBase
 * Stores trades prices and calculates profit.
 * @package App\Classes\Hitbtc
 */
class DataBase
{

    public static function addOrderRecord(string $orderId){

        DB::table(env("PROFIT_TABLE"))->insert(array(
            'symbol' => DB::table('settings_realtime')->first()->symbol,
            'order_id' => $orderId,
            'volume' => DB::table('settings_realtime')->first()->volume,
        ));
    }

    public static function addOrderInPrice($orderInPlaceTime, $orderInPlacePrice){

        DB::table(env("PROFIT_TABLE"))
            ->where('id', DB::table(env("PROFIT_TABLE"))->orderBy('id', 'desc')->first()->id)
            ->update([
                'order_in_placetime' => $orderInPlaceTime, // date("Y-m-d G:i:s")
                'order_in_placedprice' => $orderInPlacePrice
            ]);
    }

    public static function addOrderOutPrice(string $orderOutPlaceTime, $orderOutPlacePrice){

        $lastRecord = DB::table(env("PROFIT_TABLE"))->orderBy('id', 'desc')->first(); // ->id
        DB::table(env("PROFIT_TABLE"))
            ->where('id', $lastRecord->id)
            ->update([
                'order_out_placetime' => $orderOutPlaceTime,
                'order_out_placedprice' => $orderOutPlacePrice
            ]);
    }

    public static function addOrderInExecPrice($orderInExecTime, $orderInExecPrice, $rebate){

        DB::table(env("PROFIT_TABLE"))
            ->where('id', DB::table(env("PROFIT_TABLE"))->orderBy('id', 'desc')->first()->id)
            ->update([
                'order_in_exectime' => $orderInExecTime,
                'order_in_execprice' => $orderInExecPrice,
                'rebate' => abs($rebate) // Rebate value is negative
            ]);
    }

    public static function addOrderOutExecPrice($orderOutExecTime, $orderOutExecPrice, $rebate){

        $lastRecord = DB::table(env("PROFIT_TABLE"))->orderBy('id', 'desc')->first(); // ->id
        DB::table(env("PROFIT_TABLE"))
            ->where('id', $lastRecord->id)
            ->update([
                'order_out_exectime' => $orderOutExecTime,
                'order_out_execprice' => $orderOutExecPrice,
                'rebate' => abs($rebate * 2)  // Trade closed. Rebate doubled
            ]);
    }

    public static function calculateProfit(){

        $lastRecord = DB::table(env("PROFIT_TABLE"))->orderBy('id', 'desc')->first(); // ->id

        /**
         * @todo 24.10.18
         * Serious case. Very frequently order execution response does not come.
         * It means that we do not get information that the order has been filled.
         * It leads to incorrect profit calculation.
         * We make sure that we have successfully received in and out execution price and the calculate the profit.
         */
        if($lastRecord->order_out_execprice && $lastRecord->order_in_execprice){
            
            DB::table(env("PROFIT_TABLE"))
                ->where('id', $lastRecord->id)
                ->update([
                    'order_in_pricediff' => $lastRecord->order_in_placedprice - $lastRecord->order_in_execprice,
                    'order_in_duration' => strtotime($lastRecord->order_in_placetime) - strtotime($lastRecord->order_in_exectime),
                    'order_out_pricediff' => $lastRecord->order_out_placedprice - $lastRecord->order_out_execprice,
                    'order_out_duration' => strtotime($lastRecord->order_out_placetime) - strtotime($lastRecord->order_out_exectime),
                    'profit' => ($lastRecord->order_out_execprice - $lastRecord->order_in_execprice) * $lastRecord->volume,
                    'net_profit' => (($lastRecord->order_out_execprice - $lastRecord->order_in_execprice) * $lastRecord->volume) + $lastRecord->rebate,
                ]);

            DB::table(env("PROFIT_TABLE"))
                ->where('id', $lastRecord->id)
                ->update([
                    'accumulated_profit' => DB::table(env("PROFIT_TABLE"))->sum('net_profit')
                ]);
        }


    }
}