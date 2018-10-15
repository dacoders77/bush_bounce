<?php

namespace App\Jobs;

use ccxt\exmo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Classes;
use Illuminate\Support\Facades\Redis;

class PlaceLimitOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $direction;
    private $exchange;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($direction)
    {
        $this->direction = $direction;

        // Create an instance of exchange class
        $this->exchange = new Classes\Hitbtc\HitBtcPhp($_ENV['HITBTC_PUBLIC_API_KEY'], $_ENV['HITBTC_PRIVATE_API_KEY']);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $exchange = new Classes\Hitbtc\Hitbtc2();

        $exchange->apiKey = $_ENV['HITBTC_PUBLIC_API_KEY'];
        $exchange->secret = $_ENV['HITBTC_PRIVATE_API_KEY'];

        // Place order
        self::placeOrder($this->direction);
        $bid = Redis::get('bid');

        While(true) {


            // Determine whether the bid has changed
            if ($bid != Redis::get('bid')) {
                $bid = Redis::get('bid');
                $isBidChanged = true;
                //echo "------------------Bid has changed: $bid\n";
            } else {
                $isBidChanged = false;
                //echo "Bid has not changed: $bid\n";
            }


            // Is order filled?

            $count = 0;
            while (true) {
                $count++;

                // Get order status
                $response2 = $this->exchange->order(array('clientOrderId' => $this->newOrderId));
                $value = json_decode($response2, true);
                //dump($value);


                // Error: array_key_exists() expects parameter 2 to be array, null given
                if ($value['orders']) {
                    if (array_key_exists(0, $value['orders'])) {

                        echo "order status___: " . $count . " " . $value['orders'][0]['orderStatus'] . "\n";
                        event(new \App\Events\LimitOrderTrace("order status___: " . $count . " " . $value['orders'][0]['orderStatus']));

                        if ($value['orders'][0]['orderStatus'] == "filled") {
                            event(new \App\Events\LimitOrderTrace("order filled. die!**** clerntOrderId:" . $value['orders'][0]['clientOrderId']));
                            //die("order filled. die!**** clerntOrderId:" . $value['orders'][0]['clientOrderId'] );

                            echo "order filled. die!**** clerntOrderId:" . $value['orders'][0]['clientOrderId'] . "\n";
                            return;
                        }

                    }
                }


                if ($count == 5) break;
                usleep(500000); // 500000 - half a second

            }


            // Error: array_key_exists() expects parameter 2 to be array, null given
            if ($value['orders']) {

                // Sometimes you get not defined array index error. Some time is needed in order to get the status of the order
                if (array_key_exists(0, $value['orders'])) {
                    echo "order status: " . ($value['orders'][0]['orderStatus']) . " clientOrderId: " . $value['orders'][0]['clientOrderId'] . "\n";

                    //echo "bid changed/order status: " . $isBidChanged . " " . $value['orders'][0]['orderStatus'] . "\n";

                    if ($isBidChanged && $value['orders'][0]['orderStatus'] != "filled") {
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

                } else {
                    echo "array key does not exist\n";
                }

                //usleep(2000000);

            }
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
        event(new \App\Events\LimitOrderTrace($direction ." ORDER PLACED**"));

        print_r(json_decode($response, true)['ExecutionReport']['clientOrderId']);
        echo "\n";
    }
}
