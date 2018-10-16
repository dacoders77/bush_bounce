<?php

namespace App\Console\Commands;

use App\Classes\Hitbtc\Hitbtc;
use App\Classes\Hitbtc\Trading;
use App\Jobs\PlaceLimitOrder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Console\Presets\React;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Ratchet\App;
use Illuminate\Support\Facades\Cache;
use React\EventLoop\Timer\Timer;

class ccxtsocket extends Command
{

    protected $description = 'CCXT socket app';
    public $chart;
    public static $bid = null;

    private $resultOrderBookBid = array();
    private $resultOrderBookAsk = array();

    private $logMessageFlag;
    protected $connection;

    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'ccxt:start {--buy}'; // php artisan ratchet:start --init

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

        //Redis set up
        //$redis = app()->make('redis');
        //$redis->set("jo","jo");
        //dump($redis); // Output the redis object including all variables
        //echo $redis->get("jo");

/*
        // Snapshot
        $arr = array(
            ['price' => 10, 'size' => 2],
            ['price' => 11, 'size' => 17],
            ['price' => 14, 'size' => 1]
        );

        // Update
        $arr2 = array(
            ['price' => 10, 'size' => 0],
            ['price' => 18, 'size' => 44]
        );

        // An ampty array init
        $resArr = array();
        // Loop through a snapshot. Extract price and make it a regular, not associative array
        foreach ($arr as $key => $value) {
            //echo $key . " " . $value['price'] . "\n";
            array_push($resArr, $value['price']);
        }

        // Find price
        foreach ($arr2 as $key => $value)
        {
            // If price not found - add
            if (in_array($value['price'], $resArr)){
                if($value['size'] == 0){
                    unset($resArr[$key]);
                }
            }
            else{
                array_push($resArr, $value['price']);
            }
        }
        sort($resArr);


        dump($resArr);
        //dump(in_array(10, $resArr));
        die;

*/

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
        $loop->addPeriodicTimer(0.5, function() use(&$counter, $loop) { // addPeriodicTimer($interval, callable $callback)
            $counter++; // Seems like we dont need it!

            // Finish end exit from the current command
            if (Cache::get('commandExit')){
                Cache::put('commandExit', false, 5);
                echo "Exit!";
                $loop->stop();
            }

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
                            'symbol' => $this->settings = DB::table('settings_realtime')->first()->symbol,
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
                            'quantity' => '0.001'
                        ],
                        'id' => '123'
                    ]);
                    //die("die at order move");
                }

                if ($this->connection){
                    $this->connection->send($orderObject);
                }

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

                    //dump($message);

                    $this->logMessageFlag = true;
                    if (array_key_exists('method', $message)){
                        if($message['method'] != 'snapshotOrderbook'){
                            $this->logMessageFlag = false;
                        }
                    }

                    if (array_key_exists('method', $message)){
                        if($message['method'] != 'updateOrderbook'){
                            $this->logMessageFlag = false;
                        }
                    }

                    if($this->logMessageFlag){
                        dump($message);
                    }

                    $this->logMessageFlag = true;


                    if (array_key_exists('method', $message)){

                        // Bid/Ask parse
                        if($message['method'] == 'ticker'){
                            if ($message['params']['bid'] && $this->option('buy'))
                                if(array_key_exists('bid',$message['params']))
                                    $trading->parseTicker($message['params']['bid'], null);

                            if ($message['params']['bid'] && !$this->option('buy'))
                                if(array_key_exists('ask',$message['params']))
                                    $trading->parseTicker(null, $message['params']['ask']);
                        }

                        // Order condition parse
                        if($message['method'] == 'report'){
                            $trading->parseActiveOrders($message);
                        }

                        // Bid/Ask orderbook parse
                        if($message['method'] == 'updateOrderbook'){
                            if ($message['params']['bid'] && $this->option('buy')) // There can be an empty 0 element
                            {
                                $trading->parseTicker($this->updateOrderBook($message['params']['bid'], "bid"), null);
                            }
                            // ask
                            if ($message['params']['ask'] && !$this->option('buy'))
                            {
                                $trading->parseTicker(null, $this->updateOrderBook($message['params']['ask'], "ask"));
                            }

                        }

                        // Orderbook snapshot
                        if($message['method'] == 'snapshotOrderbook'){
                            $this->fillOrderBook($message['params']['bid'], "bid");
                            $this->fillOrderBook($message['params']['ask'], "ask");
                        }
                    }

                    // Order moved parse
                    if (array_key_exists('result', $message) && $message['result']['reportType'] == 'replaced'){
                            $trading->parseOrderMove($message);
                    }

                    // Error message
                    if (array_key_exists('error', $message)){
                        echo "ERROR MESSAGE HANDLED. ccxtsocket.php 276";
                        $loop->stop();
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
                $conn->send($subscribeTicker); // First subscription object
                //$conn->send($subscribeOrderBook);
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

    /*
     * Fill two order book arrays: bid and ask.
     * Snapshot data is used. Then this snapshot(array) will be updated in each order book update message
     */
    private function fillOrderBook(array $array, string $orderBookPart){

        if ($orderBookPart == "bid")
            $orderBook = $this->resultOrderBookBid;
        else
            $orderBook = $this->resultOrderBookAsk;
        foreach ($array as $key => $value) {
            array_push($orderBook, $value['price']); // Fill array only with price values
        }
    }

    /*
     * Update arrays: bid and ask.
     * @return double
     */
    private function updateOrderBook(array $array, string $orderBookPart){

        if ($orderBookPart == "bid")
            $orderBook = $this->resultOrderBookBid;
        else
            $orderBook = $this->resultOrderBookAsk;

        foreach ($array as $key => $value)
        {
            // If price not found - add. If size = 0 - remove this price level from the array
            if (in_array($value['price'], $orderBook)){
                if($value['size'] == 0){
                    unset($orderBook[$key]); // If size = 0, remove this price level. https://api.hitbtc.com/#subscribe-to-orderbook
                }
            }
            else{
                array_push($orderBook, $value['price']);
            }
        }
        if ($orderBookPart == "bid")
            rsort($orderBook);
        else
            sort($orderBook);

        return($orderBook[0]);
        //return(array('params' => ['bid' => $orderBook[0]]));
    }
}
