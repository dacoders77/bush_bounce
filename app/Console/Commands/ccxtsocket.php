<?php

namespace App\Console\Commands;

use App\Classes\Hitbtc\DataBase;
use App\Classes\Hitbtc\Hitbtc;
use App\Classes\Hitbtc\OrderObject;
use App\Classes\Hitbtc\Trading;
use App\Classes\LogToFile;
use App\Jobs\PlaceLimitOrder;
use App\Mail\EmptyEmail;
use App\Mail\EmptyEmail2;
use Illuminate\Console\Command;
use Illuminate\Foundation\Console\Presets\React;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Ratchet\App;
use Illuminate\Support\Facades\Cache;
use React\EventLoop\ExtEventLoop;
use React\EventLoop\Factory;
use React\EventLoop\Timer\Timer;
use Symfony\Component\VarDumper\Cloner\Data;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

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

    public $tradesArray = array();
    public $accumulatedOrderVolume;
    public $averageOrderFillPrice;
    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {

        /*
        array_push($this->tradesArray,   new OrderObject("", "", 120, 10));
        array_push($this->tradesArray, new OrderObject("", "", 130, 10));

        foreach ($this->tradesArray as $trade){
            echo "Trading.php 147:\n";
            dump($trade);
            $this->averageOrderFillPrice = $this->averageOrderFillPrice + $trade->price;
            $this->accumulatedOrderVolume += $trade->quantity; // THIS IS WRONG
        }
        $this->averageOrderFillPrice = $this->averageOrderFillPrice / count($this->tradesArray);

        echo "--------------------ACCUMM VOLL: " . $this->accumulatedOrderVolume . "\n";
        echo "AVG PRICE: " . $this->averageOrderFillPrice;

        die();
        */


        //Redis set up
        //$redis = app()->make('redis');
        //$redis->set("jo","jo");
        //dump($redis); // Output the redis object including all variables
        //echo $redis->get("jo");

        $trading = new \App\Classes\Hitbtc\Trading();

        echo "***** CCXT websocket app started! ccxtsocket.php line 100 *****\n";

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

        // MOVE TO SEPARATE METHOD!
        /* React loop cycle */
        $loop->addPeriodicTimer(0.5, function() use($loop) { // addPeriodicTimer($interval, callable $callback)

            // Finish end exit from the current command
            if (Cache::get('commandExit' . env("ASSET_TABLE"))){
                Cache::put('commandExit' . env("ASSET_TABLE"), false, 5);
                echo "Exit!";
                $loop->stop();
            }

            // Cache setup
            if (Cache::get('orderObject' . env("ASSET_TABLE")) != null)
            {
                $value = Cache::get('orderObject' . env("ASSET_TABLE"));
                if ($value->action == "placeOrder"){
                    // Place order with the price and volume from cache
                    $orderObject = json_encode([
                        'method' => 'newOrder',
                        'params' => [
                            'clientOrderId' => $value->orderId,
                            'symbol' => DB::table('settings_realtime')->first()->symbol,
                            'side' => $value->direction,
                            'type' => 'limit',
                            'price' => $value->price,
                            'quantity' => $value->quantity
                        ],
                        'id' => '123'
                    ]);
                }

                if ($value->action == "moveOrder")
                {
                    // Move order
                    $orderObject = json_encode([
                        'method' => 'cancelReplaceOrder',
                        'params' => [
                            'clientOrderId' => $value->orderId, // Id of the order to be moved
                            'requestClientId' => $value->newOrderId, // New order
                            'price' => $value->price,
                            'quantity' => $value->quantity
                        ],
                        'id' => '123'
                    ]);
                }

                if($value->action == "getActiveOrders"){
                    $orderObject = [];
                }

                if ($this->connection){
                    $this->connection->send(json_encode(['method' => 'getOrders', 'params' => [], 'id' => '123'])); // Get order statuses
                    if ($orderObject) $this->connection->send($orderObject); // Send object to websocket stream;
                    //$this->connection->send(json_encode(['method' => 'getTradingBalance', 'params' => [], 'id' => '123'])); // Get trading balances
                }

                Cache::put('orderObject' . env("ASSET_TABLE"), null, now()->addMinute(5)); // Clear the cache. Assigned value Expires in 5 minutes
            }
        });

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector('wss://api.hitbtc.com/api/2/ws', [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop, $trading) {
                $this->connection = $conn; // For accessing conn outside of the this unanimous func
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop, $trading) {

                    $message = json_decode($socketMessage->getPayload(), true);
                    $this->webSocketMessageParse($loop, $trading, $message);
                });

                $conn->on('close', function($code = null, $reason = null) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $this->info("line 212. connection closed");
                    $this->error("Reconnecting back in 5 seconds!");

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
                    'id' => 123
                ]);

                /* Websocket subscription */
                $conn->send($authObject);
                $conn->send($subscribeTicker); // First subscription object
                //$conn->send($subscribeOrderBook);
                $conn->send($subscribeReports); // Order statuses. Filled, new etc.



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

    /*
     * Websocket message parse.
     * Output trace messages to console and filtering.
     * @param   ReactEventLoop $loop Used to stop the loop.
     * @param   Trading $trading Trading class instance where an order is placed and moved.
     * @param   array $message Json decoded websocket stream message.
     * @return void
     */
    private function webSocketMessageParse($loop, Trading $trading, array $message){

        /* Output all messages. No filters. Heavy output! */
        //dump($message); 


        /* Set messages that should not outputed */
        $this->logMessageFlag = true;
        if (array_key_exists('method', $message)){
            if($message['method'] != 'snapshotOrderbook'){
                $this->logMessageFlag = false; // If flag is set - dont output this type of message
            }
        }

        if (array_key_exists('method', $message)){
            if($message['method'] != 'updateOrderbook'){
                $this->logMessageFlag = false;
            }
        }

        if (array_key_exists('result', $message)){ //
            if(gettype($message['result']) == 'array'){
               /* When there is 'id' in the array - this is the array of values. This is an order status message.
                * In other cases this this is the array of arrays - balances message.
                */
                if (!array_key_exists('status', $message['result'])){
                    $this->logMessageFlag = false;
                }
            }
        }

        /* Main log output */
        if($this->logMessageFlag){
            //dump("ccxtsocket.php 226. MAIN LOG:");
           // dump($message);
        }
        $this->logMessageFlag = true;

        /* Place order */
        if (array_key_exists('method', $message)){
            /* Bid/Ask parse */
            if($message['method'] == 'ticker'){
                if ($message['params']['bid'] && $this->option('buy')) // ccxt:start --buy
                    if(array_key_exists('bid', $message['params']))
                        $trading->parseTicker($message['params']['bid'], null);

                if ($message['params']['bid'] && !$this->option('buy')) // // ccxt:start --NO PARAM
                    if(array_key_exists('ask', $message['params']))
                        $trading->parseTicker(null, $message['params']['ask']);
            }

            /* Order condition parse.
               Order placed and order filled statuses.
             */
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

            /* Orderbook snapshot */
            if($message['method'] == 'snapshotOrderbook'){
                $this->fillOrderBook($message['params']['bid'], "bid");
                $this->fillOrderBook($message['params']['ask'], "ask");
            }
        }

        if(array_key_exists('result', $message) && $message['result'] != []) {
            if (gettype($message['result']) != "boolean") {
                /**
                 * If there is 'id' key - means that this is an array of values. Place order response
                 * Here we get two type of messages:
                 * 1. When it is array of values: New and Replaced orders (moved)
                 * 2. Array of arrays: when there is more than one order at the exchange
                 */
                if (array_key_exists('id', $message['result'])) {
                    if ($message['result']['reportType'] == "replaced"){
                        $trading->parseOrderMove($message['result']);
                    }
                }
                else{
                    if(array_key_exists('id', $message['result'][0])){ // Orders
                        /* Get statuses response */
                        foreach ($message['result'] as $order)
                        {
                            //dump($message);
                            if ($order['reportType'] == "replaced"){
                                $trading->parseOrderMove($order);
                            }
                        }
                    }

                    if (array_key_exists('currency', $message['result'][0])){ // Trading balances
                        foreach ($message['result'] as $balanceRecord){
                            if($balanceRecord['currency'] == "ETH"){
                                echo "ccxtsocket.php 409. Balance: ";
                                dump($balanceRecord['currency'] . " " . $balanceRecord['available']);
                            }
                        }
                    }
                }
            }
        }

        /* Error message handle */
        if (array_key_exists('error', $message)){
            echo "ERROR MESSAGE HANDLED!. Exit. ccxtsocket.php 454. THREAD NOT STOPED!\n";
            dump($message);
            /* Email notification */
            //$objDemo = new \stdClass();
            //$objDemo->subject = 'BUSH error: ' . env("ASSET_TABLE") . " " . DB::table('settings_realtime')->first()->symbol;
            //$objDemo->body = "Error code: " . $message['error']['code'] . " Message: " . $message['error']['message'] . " Description: " . $message['error']['description'] . " Time: " . date("Y-m-d G:i:s");
            //$emails = ['nextbb@yandex.ru', 'aleksey.kirushin2015@yandex.ru', 'Ikorepov@gmail.com', 'busch.art@yandex.ru'];
            //Mail::to($emails)->send(new EmptyEmail($objDemo));

            $loop->stop(); // Exit from this thread. If not to - app freezes after error Order not found pops up
            //$loop->run(); // Don't use it


        }
    }
}
