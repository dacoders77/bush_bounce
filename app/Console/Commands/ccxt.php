<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
//include 'ccxt.php';


use Illuminate\Support\Facades\DB;
use App\Classes;
use Illuminate\Support\Facades\Log;

class ccxt extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccxt:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //$poloniex = new \ccxt\poloniex (); // Works good
        //$poloniex_markets = $poloniex->load_markets ();
        //dump($poloniex_markets);

        /**
         * https://github.com/reactphp/event-loop#futuretick
         */
        $loop = \React\EventLoop\Factory::create();

        // instantiate the exchange by id
        $exchange = '\\ccxt\\hitbtc'; // poloniex
        $exchange = new $exchange ();

        //dump($exchange->fetchMarkets()); // Works good
        //dump($exchange->fetchTicker('BTC/USDT')['bid']); // Works good


        $tick_function = function () use ($exchange, $loop, &$tick_function) {
            //global $exchange, $loop;
            $order_book = $exchange->fetch_order_book('ETH/BTC');

            //echo "----------------------------------------------------------------\n";
            //echo date ('c') . "\n";
            //echo count ($order_book['bids']) . " bids and " . count ($order_book['asks']) . " asks\n";
            //echo sprintf ("bid: %.8f ask: %.8f", $order_book['bids'][0][0], $order_book['asks'][0][0]) . "\n";

            echo "bid: " . $exchange->fetchTicker('BTC/USDT')['bid'] . " ask: " . $exchange->fetchTicker('BTC/USDT')['ask'] . "\n";

            $loop->futureTick ($tick_function);

        };
        $loop->futureTick ($tick_function);
        $loop->run ();




    }
}
