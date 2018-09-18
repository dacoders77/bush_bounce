<?php

namespace App\Console\Commands;

use ccxt\ccex;
use ccxt\hitbtc2;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Classes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use PhpParser\Node\Stmt\While_;

class ccxt extends Command
{
    private $exchange;
    private $bid = null;
    private $orderId;
    private $request;
    private $newOrderId;
    private $direction;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccxtd:start {direction?}';

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

        // Create an instance of exchange class
        $this->exchange = new Classes\Hitbtc\HitBtcPhp($_ENV['HITBTC_PUBLIC_API_KEY'], $_ENV['HITBTC_PRIVATE_API_KEY']);

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //$exchange = new \ccxt\hitbtc2();
        //$exchange->apiKey = '';
        //$exchange->secret = '';
        //dump($exchange->fetchBalance()); // hitbtc2.php line 660
        //dump($exchange->fetch2("trading/balance", "")); // Works good

        //dd($exchange->urls);

        /* Recreating exchange
        1. tetch2() method is called from Exchnage.php line 564
        2. sign() Auth headers are prepared, encoded (base64_encode) and stored in $request variable. Hitbtc2.php line 550
        3. $request['url'], $request['method'], $request['headers'], $request['body'] are sent as parameters to fetch()
        Exchange.php line 282. Curl headers are sent, response and errors are handled
        4.
        */
        $exchange = new Classes\Hitbtc\Hitbtc2(); // Classes\Hitbtc\Exchange() - try it

        $exchange->apiKey = $_ENV['HITBTC_PUBLIC_API_KEY'];
        $exchange->secret = $_ENV['HITBTC_PRIVATE_API_KEY'];

        /** Api endpoind, public/privet endpoint type. Privet - all authenticated methods */
        //$request = $exchange->fetch2("trading/balance", "private"); // Works good https://api.hitbtc.com/api/2/trading/balance
        //dump($request);


        // Cancel order

        /*
        $exchange->version = 1;
        $request2 = $exchange->fetch2(
            "trading/order", // trading/order
            "privet",
            "GET");
        dump($request2);
        */


        // Set asset in DB to ethbtc +
        // Add to ccxtd - symbol frim DB call +
        // Set trading allowed to false +
        // When long signal is fired - call ccxtd command
        // ccxtd command must be running and feed redis bid/ask quotes
        // Turn off quotes events


        // Use command line arguments. In testing purposes only
        if($this->argument('direction') == "buy"){
            $this->direction = "buy";
        }
        else {
            $this->direction = "sell";
        }


        // Place order
        self::placeOrder($this->direction);
        $bid = Redis::get('bid');

        While(true){



            // Determine whether the bid has changed
            if ($bid != Redis::get('bid'))
            {
                $bid = Redis::get('bid');
                $isBidChanged = true;
                //echo "------------------Bid has changed: $bid\n";
            }
            else
            {
                $isBidChanged = false;
                //echo "Bid has not changed: $bid\n";
            }




            // Is order filled?

            $count = 0;
            while (true){
                $count++;

                // Get order status
                $response2 = $this->exchange->order(array('clientOrderId' => $this->newOrderId));
                $value = json_decode($response2, true);
                //dump($value);



                if (array_key_exists(0, $value['orders'])){

                    echo "order status___: " . $count . " " . $value['orders'][0]['orderStatus'] . "\n";

                    if ($value['orders'][0]['orderStatus'] == "filled"){
                        die("order filled. die!**** clerntOrderId:" . $value['orders'][0]['clientOrderId'] );
                    }

                }
                if ($count == 5) break;
                usleep(500000);

            }






            // Sometimes you get not defined array index error. Some time is needed in order to het the status of the order
            if (array_key_exists(0, $value['orders'])){
                echo "order status: " . ($value['orders'][0]['orderStatus']) . " clientOrderId: " . $value['orders'][0]['clientOrderId'] . "\n";

                //echo "bid changed/order status: " . $isBidChanged . " " . $value['orders'][0]['orderStatus'] . "\n";

                if ($isBidChanged && $value['orders'][0]['orderStatus'] != "filled"){
                    echo "************************Cancel anf place new order\n";

                    $response = $this->exchange->cancel_order(array(
                        'cancelRequestClientOrderId' => self::randomString(rand(8, 30)),
                        'clientOrderId' => $this->newOrderId,
                        'symbol' => 'ethbtc',
                        'side' => 'buy'
                    ));
                    
                    //dump($response);

                    // Previous order is canceled
                    // Place new order
                    
                    self::placeOrder($this->direction);

                }

            }
            else
            {
                echo "array key does not exist\n";
            }

            //usleep(2000000);




        }




    }

    function randomString($length) {
        $key = '';
        $keys = array_merge(range(0, 9), range('a', 'z'));
        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
        return $key;
    }

    private function placeOrder($direction){


        /** Place order**/
        $this->newOrderId = Self::randomString(rand(8, 30));

        $response = $this->exchange->new_order(array(
            'clientOrderId' => $this->newOrderId,
            'symbol' => 'ETHBTC', //BTCUSD
            'side' => $direction,
            'price' => ($direction == "buy" ? Redis::get('bid') : Redis::get('ask')),
            'quantity' => 1, // 1 lot => 0.01 BTC
            'type' => 'limit',
            'timeInForce' => 'GTC'
        ));

        echo "                      PLACED ORDER: ";
        print_r(json_decode($response, true)['ExecutionReport']['clientOrderId']);
        echo "\n";
    }




}