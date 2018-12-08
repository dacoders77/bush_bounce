<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 12/8/2018
 * Time: 1:25 AM
 */

namespace App\Classes\Hitbtc;

use ccxt\hitbtc2;
use Illuminate\Support\Facades\DB;

class Position
{
    public static function checkPosition(){

        $exchange = new hitbtc2(); // new hitbtc2()
        $exchange->apiKey = $_ENV['HITBTC_PUBLIC_API_KEY'] ;
        $exchange->secret = $_ENV['HITBTC_PRIVATE_API_KEY'];
        $activeOrders = $exchange->privateGetOrder(['symbol' => DB::table('settings_realtime')->first()->symbol ]);

        If (!$activeOrders && (DB::table('asset_1')->where('trade_direction', '!=', null)->count() != 0)){
            dump('ENTERED POSITION CHECKER ' . __FILE__);
            /* Get current position based on the signal generated on chart */
            $lastTradeDirection = (DB::table('asset_1')
                ->orderBy('id', 'desc')
                ->where('trade_direction', '!=', null)
                ->first())->trade_direction;

            /* Get trading position from the exchange */
            $parts = explode("/", DB::table('settings_realtime')->first()->symbol_market);
            $currency = $parts[0];
            $balance = $exchange->fetchBalance()[$currency];

            if($lastTradeDirection == 'buy'){
                if ($balance['total'] > 0){
                    dump('Buy position is correct. No actions needed.');
                }
                else{
                    dump('Position is not equal to the signal! Need to open long!');
                    // Open long with planned value
                    try{
                        $response = $exchange->createMarketBuyOrder(DB::table('settings_realtime')->first()->symbol_market, DB::table('settings_realtime')->first()->volume, []);
                    }
                    catch (\Exception $e){
                        dump('Sell order error: ' . $e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
                    }
                }
            }
            if($lastTradeDirection == 'sell'){
                if ($balance['total'] == 0){
                    dump('Sell position is correct. No actions needed.');
                }
                else{
                    dump('Position is not equal to the signal! Need to close long!!');
                    // Close long with balance value
                    try{
                        $response = $exchange->createMarketSellOrder(DB::table('settings_realtime')->first()->symbol_market, $balance['total'], []);
                    }
                    catch (\Exception $e){
                        dump('Sell order error: ' . $e->getMessage() . ' ' . __FILE__ . ' ' . __LINE__);
                    }
                }
            }
        }
        else
        {
            //dump('There are active orders. No need to check positions. ' . __FILE__ . ' ' . __LINE__  );
        }
    }
}