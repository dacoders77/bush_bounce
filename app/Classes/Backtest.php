<?php
/**
 * Created by PhpStorm.
 * User: slinger
 * Date: 6/12/2018
 * Time: 7:58 PM
 */

namespace App\Classes;

/**
 * Class Backtest
 * This class takes historical bars loaded from www.bitfinex.com one by one
 * and calculates profit. Calculated profit, positions, accumulated profit are recorded to DB.
 * This class simulates real ticks coming from the exchange. In this case only one tick per bar will be generated - close.
 *
 * @package App\Classes
 */
class Backtest
{
    public function start(){

        // Get all records from DB

        // Foreach them, one by one

        // On each iteration call Chart::index(bar date, close price)

    }

}