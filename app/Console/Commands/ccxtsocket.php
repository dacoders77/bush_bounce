<?php

namespace App\Console\Commands;

use App\Jobs\PlaceLimitOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Ratchet\App;
use Illuminate\Support\Facades\Cache;

class ccxtsocket extends Command
{

    protected $description = 'CCXT socket app';
    public $chart;
    public static $bid = null;


    protected $connection;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'ccxt:start'; // php artisan ratchet:start --init

    /**
     * The console command description.
     * @var string
     */


    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        // DO NOT PLACE CODE SERIOUS IN THE CONSTRUCTOR
        // CONSTRUCTORS ARE CALLED WHEN APPLICATION STARTS (the whole laravel!) AND MAY CAUSE DIFFERENT PROBLEMS
    }

    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {

        // Redis set up
        //$redis = app()->make('redis');
        //$redis->set("jo","jo");
        //dump($redis); // Output the redis object including all variables
        //echo $redis->get("jo");

        $trading = new \App\Classes\Hitbtc\Trading();

        echo "***** CCXT websocket app started!*****\n";

        /**
         * Reset trade flag. If it is not reseted, it will contain previous position state.
         * Reset code is moved to a separate controller.
         * Do the reset only if the command was started from comsole, we check input option
         * in order to determine wwhether the command was started manually or caused by reconnect.
         * $this->option('init') can not be set to false that is why We use additional flag to do
         * the initial start only when started from console
         */



        /**
         * Ratchet/pawl websocket lib
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, [
            'dns' => '8.8.8.8', // Does not work through OKADO internet provider. Timeout error
            'timeout' => 10
        ]);

        /* React loop cycle */
        $counter = 0;
        $loop->addPeriodicTimer(0.5, function() use(&$counter) { // addPeriodicTimer($interval, callable $callback)
            $counter++; // Seems like we dont need it!

            // Cache setup
            if (Cache::get('orderObject') != null)
            {
                $value = Cache::get('orderObject');

                if (!$value->moveOrder){

                    // Place order with the price from cache
                    $orderObject = json_encode([
                        'method' => 'newOrder',
                        'params' => [
                            'clientOrderId' => $value->orderId,
                            'symbol' => 'ETHBTC',
                            'side' => $value->direction,
                            'type' => 'limit',
                            'price' => $value->price,
                            'quantity' => '0.001'
                        ],
                        'id' => '123'
                    ]);
                    //die('on placed order');
                }

                if ($value->moveOrder)
                {
                    // Move order
                    //echo "Need to move the order";
                    $orderObject = json_encode([
                        'method' => 'cancelReplaceOrder',
                        'params' => [
                            'clientOrderId' => $value->orderId, // Id of the order to be moved
                            'requestClientId' => $value->newOrderId, // New order
                            'price' => $value->price,
                            'quantity' => '0.002'
                        ],
                        'id' => '123'
                    ]);
                    //die("die at order move");
                }

                if ($this->connection)
                    $this->connection->send($orderObject);

                Cache::put('orderObject', null, now()->addMinute(5)); // Expires in 5 minutes
            }



        });


        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector('wss://api.hitbtc.com/api/2/ws', [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop, $trading) {
                $this->connection = $conn; // For accessing conn outside of the this unanimous func
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop, $trading) {

                    // Code parse goes here
                    $message = json_decode($socketMessage->getPayload(), true);



                    if (array_key_exists('method', $message)){
                        if($message['method'] != 'ticker'){
                        }
                    }

                    //dump ($message);


                    if (array_key_exists('method', $message)){

                        // Bid/Ask parse
                        if($message['method'] == 'ticker'){
                            $trading->parseTicker($message);
                        }

                        // Order condition parse
                        if($message['method'] == 'report'){
                            $trading->parseActiveOrders($message);
                        }

                        // Bid/Ask parse
                        if($message['method'] == 'updateOrderbook'){
                            //$trading->parseTicker($message['params']['bid']);
                            dump($message);
                            if ($message['params']['bid']) // There can be an empty 0 element
                            {
                                //dump($message['params']['bid'][0]['price']);
                                //$arr = ['params' => ['bid' => $message['params']['bid'][0]['price']   ]];
                                //$trading->parseTicker($arr);
                            }
                        }

                        // Bid/Ask parse
                        if($message['method'] == 'snapshotOrderbook'){
                            //$trading->parseTicker($message['params']['bid']);
                            dump($message['params']['ask'][0]);
                            die();
                        }
                    }

                    // Order moved parse
                    if (array_key_exists('result', $message) && $message['result']['reportType'] == 'replaced'){
                        $trading->parseOrderMove($message);
                    }


                    if (array_key_exists('method', $message)){

                        // subscribeTrades WORKS GOOD
                        /*
                        if($message['method'] == 'updateTrades'){
                            $timestamp = strtotime($message['params']['data'][0]['timestamp']) * 1000;
                            echo $timestamp . " ";
                            echo $message['params']['data'][0]['side'] . " ";
                            echo $message['params']['data'][0]['price'] . "\n";
                        }
                        */


                        if($message['method'] == 'ticker'){
                            $timestamp = strtotime($message['params']['timestamp']) * 1000;
                            //echo $timestamp . " ";
                            //echo $message['params']['bid'] . " ";
                            //echo $message['params']['ask'] . "\n";

                            Redis::set('bid', $message['params']['bid']); // Assign redis key-pair value
                            Redis::set('ask', $message['params']['ask']);

                            // Place job onto the que. DELETE
                            //PlaceLimitOrder::dispatch("kkk");
                        }

                    }


                });
                $conn->on('close', function($code = null, $reason = null) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $this->info("line 82. connection closed");
                    $this->error("Reconnecting back!");

                    sleep(5); // Wait 5 seconds before next connection try will attpemt
                    $this->handle(); // Call the main method of this class
                });

                /* Works good
                $z = json_encode([
                    'method' => 'getCurrency',
                    'params' => [
                        'currency' => 'ETH'
                    ],
                    'id' => 123
                ]);
                */

                /* WORKS GOOD
                $z = json_encode([
                    'method' => 'subscribeTrades',
                    'params' => [
                        'symbol' => 'BTCUSD' // ETHBTC
                    ],
                    'id' => 123
                ]);
                */

                $authObject = json_encode([
                    'method' => 'login',
                    'params' => [
                        'algo' => 'BASIC',
                        'pKey'=> $_ENV['HITBTC_PUBLIC_API_KEY'],
                        'sKey' => $_ENV['HITBTC_PRIVATE_API_KEY']
                    ]
                ]);

                $subscribeTicker = json_encode([
                    'method' => 'subscribeTicker',
                    'params' => [
                        'symbol' => $this->settings = DB::table('settings_realtime')->first()->symbol // ETHBTC BTCUSD
                        //'symbol' => 'ethbtc' // ETHBTC ethbtc
                    ],
                    'id' => 123
                ]);

                $subscribeOrderBook = json_encode([
                    'method' => 'subscribeOrderbook',
                    'params' => [
                        'symbol' => $this->settings = DB::table('settings_realtime')->first()->symbol // ETHBTC BTCUSD
                    ],
                    'id' => 123
                ]);



                $subscribeReports = json_encode([
                    'method' => 'subscribeReports',
                    'params' => [],
                ]);

                $conn->send($authObject);
                //$conn->send($subscribeTicker); // First subscription object
                $conn->send($subscribeOrderBook);
                $conn->send($subscribeReports);


                /** @todo Add sleep function, for example 1 minute, after which reconnection attempt will be performed again */
            }, function(\Exception $e) use ($loop) {
                $errorString = "RatchetPawlSocket.php line 210. Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;

                sleep(5); // Wait 5 seconds before next connection try will attpemt
                $this->handle(); // Call the main method of this class
                //$loop->stop();
            });

        $loop->run();

    }

}
