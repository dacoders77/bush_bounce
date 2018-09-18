<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class LimitOrder extends Command
{


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'limit:start';

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
        //echo Redis::get('bid') . " " . Redis::get('ask');
        $this->orderId = null;


        $exchange = new \ccxt\hitbtc(array(
            'apiKey' => $_ENV['HITBTC_PUBLIC_API_KEY'],
            'secret' => $_ENV['HITBTC_PRIVATE_API_KEY'],
        ));

        $symbol = "BTC/USDT";


        while (true){

            if ($this->orderId == null){ // First run. Place order. Else - check status

                echo "place order\n";
                $this->orderPlaceResponce = $exchange->createLimitBuyOrder($symbol, 0.01, Redis::get('bid') - 0); // //$exchange.createLimitBuyOrder (symbol, amount, price[, params])
                //dump($this->orderPlaceResponce);

                echo "place price: " . (Redis::get('bid') - 1) . "\n";
                $this->orderId = $this->orderPlaceResponce['id'];
            }
            else{
                usleep(3000000); // Wait 3 sec

                //echo $this->orderPlaceResponce['status'] . "\n";
                $fetchOrderResponse = $exchange->fetchOrder($this->orderId);
                echo "--------Status: " . $fetchOrderResponse['status'];

                if($fetchOrderResponse['status'] != "closed"){ // If status != close means that order should be canceled and placed again
                    echo "Move order: " . $this->orderPlaceResponce['id'] . "\n";
                    $exchange->cancelOrder($this->orderPlaceResponce['id']);
                    $this->orderId = null; // Set it in order to enter the first if
                }
                else{
                    echo "order filled! die.\n";
                    $fetchOrderResponse = $exchange->fetchOrder($this->orderId);
                    echo "--------filled price: " . $fetchOrderResponse['price'] . "\n";
                    //dump($fetchOrderResponse);
                    die();
                }
            }





        }


    }
}
