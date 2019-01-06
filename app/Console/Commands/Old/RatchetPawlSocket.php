<?php

namespace App\Console\Commands;
use App\Jobs\PlaceLimitOrder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Classes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use ccxt\hitbtc2;
use App\FactOrder; // Model link
use Illuminate\Support\Facades\Cache;

class RatchetPawlSocket extends Command
{
    /* @var bool $isFirstTimeBroadcastCheck First time running broadcast check. Is used only once the app is started*/
    //private $isFirstTimeBroadcastCheck = true;

    /* @var bool $isFirstTimeTickCheck First tick check. Used in order to decrease quantity of ticks because pusher limit exceeds sometime*/
    //private $isFirstTimeTickCheck = true;

    /* @var integer $addedTime Used in order to determine whether the broadcast is allowed or not. This check is performed once a second */
    //private $addedTime = null;

    /* @var integer $addedTickTime The same but for ticks*/
    // private $addedTickTime = null;

    /* @var bool $isBroadCastAllowed Flag whether to allow broadcasting or not. This flag is retrieved from the DB onece a second */
    //private $isBroadCastAllowed;

    private $settings;

    /* @var bool $initStartFlag Sybolyses when the command was executed from artsan console */
    private $initStartFlag = true;

    protected $connection;

    /**
     * The name and signature of the console command.
     * @see https://laravel.com/docs/5.7/artisan#input-arrays
     * @var string
     * @see https://interactivebrokers.github.io/tws-api/historical_bars.html
     * Params: init, backtest, etc.
     * Only if init:
     * 1. symbol
     * 2. currency
     * 3. queryTime
     * 4. duration (days)
     * 5. timeFrame ()
     */
    protected $signature = 'ratchet:start {--param=*}'; // php artisan ratchet:start --init. Array input!

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ratchet/pawl websocket client console application';
    public $chart;

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
    public function handle(Classes\Chart $chart, Classes\CandleMaker $candleMaker)
    {

        echo "*****Ratchet websocket console command(app) started!*****\n";
        echo "initial start flag from console = ";
        dump($this->option('param'));
        event(new \App\Events\ConnectionError("Ratchet started"));

        Log::useDailyFiles(storage_path() . '/logs/debug.log'); // Setup log name and math. Logs are created daily
        Log::debug("*****Ratchet websocket console command(app) started!*****");
        Log::debug("initial start flag from console = " . json_encode($this->option('param')));

        /**
         * Reset trade flag. If it is not reseted, it will contain previous position state.
         * Reset code is moved to a separate controller.
         * Do the reset only if the command was started from comsole, we check input option
         * in order to determine wwhether the command was started manually or caused by reconnect.
         * $this->option('init') can not be set to false that is why We use additional flag to do
         * the initial start only when started from console
         */

        if ($this->option('param')[0] == 'init' && $this->initStartFlag) $this->startInit();
        if ($this->option('param')[0] == 'buy' && $this->initStartFlag) $this->startBuy(); // Throw a test trade. Used when there is no time to wait for signal at the chart
        if ($this->option('param')[0] == 'sell' && $this->initStartFlag) $this->startSell();
        if ($this->option('param')[0] == 'channel' && $this->initStartFlag) $this->priceChannelCalc();
        if ($this->option('param')[0] == 'backtest' && $this->initStartFlag) $this->backTestStart();

        /**
         * Ratchet/pawl websocket lib
         * @see https://github.com/ratchetphp/Pawl
         */
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, [
            'dns' => '8.8.8.8', // Does not work through OKADO internet provider. Timeout error
            'timeout' => 10
        ]);

        // CACHE GOES HERE. Listen cache
        $loop->addPeriodicTimer(0.5, function() use($loop) { // addPeriodicTimer($interval, callable $callback)
            $this->listenCacheQue();
        });

        /* Periodic check for correct position condition. Sometimes orders accidentally cancel whiteout opening a position. */
        $loop->addPeriodicTimer(10, function () use ($loop) {
            //Classes\Hitbtc\Position::checkPosition($this->exchange);
        });

        $loop->addPeriodicTimer(12, function () use ($loop) {
            /* Get trades and calculate profit (based and actual trades received from the exchange) */
            //Artisan::queue("stat:start", ["--from" => "2018-12-08 07:28:40"])->onQueue(env("DB_DATABASE"));
        });

        $this->settings = DB::table('settings_realtime')->first();

        // Init start - fact_orders table is truncated
        // First request is made when orders table has a first record in it
        // The ann request use the date from last record in fact_orders table


        if ($this->option('param')[0] != 'channel' ) {
            if ($this->option('param')[0] != 'backtest') {

                $connector = new \Ratchet\Client\Connector($loop, $reactConnector);
                $connector("ws://localhost:8181", [], ['Origin' => 'http://localhost'])
                    ->then(function (\Ratchet\Client\WebSocket $conn) use ($chart, $candleMaker, $loop) {
                        $this->connection = $conn; // For accessing conn outside of the this unanimous func
                        $conn->on('message', function (\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $chart, $candleMaker, $loop) {
                            $nojsonMessage = json_decode($socketMessage->getPayload(), true);
                            if ($nojsonMessage) $this->parseWebSocketMessage($nojsonMessage, $candleMaker, $chart, $this);
                            /*
                            if (array_key_exists('method', $nojsonMessage)) {

                                $timestamp = strtotime($nojsonMessage['params']['data'][0]['timestamp']) * 1000;

                                // Check whether broadcast is allowed only once a second
                                if ($this->isFirstTimeBroadcastCheck || $timestamp >= $this->addedTime) {
                                    $this->addedTime = $timestamp + 1000;
                                    $this->isFirstTimeBroadcastCheck = false;

                                    // @var collection $settings The whole row from settings table.
                                    //  Passed to CandleMaker. The reason to locate this variable here is to read this value only once a second.
                                    //  We already have this functionality here - broadcast allowed check
                                    $this->settings = DB::table('settings_realtime')->first(); // Read settings row and pass it to CandleMaker as a parameter

                                    if (DB::table('settings_realtime')
                                            ->where('id', 1)
                                            ->value('broadcast_stop') == 0) {
                                        $this->isBroadCastAllowed = true;
                                    } else {
                                        $this->isBroadCastAllowed = false;
                                        echo "RatchetPawlSocket.php Broadcast flag is set to FALSE. line 226  \n";
                                        event(new \App\Events\ConnectionError("Broadcast stopped. " . (new \DateTime())->format('H:i:s')));
                                    }
                                }


                                 // 1st condition $this->isFirstTimeTickCheck - enter here only once when the app starts
                                 // 2nd tick time > computed time and broadcast is allowed

                                if ($this->isFirstTimeTickCheck || ($timestamp >= $this->addedTickTime && $this->isBroadCastAllowed)) {

                                    $this->isFirstTimeTickCheck = false;
                                    $this->addedTickTime = $timestamp + $this->settings->skip_ticks_msec; // Allow to send ticks not more frequent that twice a second
                                    //
                                    //  @param double $nojsonMessage [2][3] ($tickPrice) Price of the trade
                                    //  @param integer $nojsonMessage [2][1] ($tickDate) Timestamp
                                    //  @param double $nojsonMessage [2][2] ($tickVolume) Volume of the trade
                                    //  @param Classes\Chart $chart Chart class instance
                                    //  @param collection $settings Row of settings from DB
                                    //  @param command $command variable for graphical strings output to the console
                                    //
                                    $candleMaker->index($nojsonMessage['params']['data'][0]['price'], $timestamp, $nojsonMessage['params']['data'][0]['quantity'], $chart, $this->settings, $this);
                                }
                            }
                            */

                        });

                        $conn->on('close', function ($code = null, $reason = null) use ($chart, $candleMaker) {
                            echo "Connection closed ({$code} - {$reason})\n";
                            $this->info("line 82. connection closed");
                            $this->error("Reconnecting back!");
                            Log::debug("RatchetPawlSocket.php line 181. Connection lost. Reconnecting back!");
                            sleep(5); // Wait 5 seconds before next connection try will attempt
                            $this->handle($chart, $candleMaker); // Call the main method of this class
                        });

                        /*
                        $requestObject = json_encode([
                            'method' => 'subscribeTrades',
                            'params' => [
                                'symbol' => $this->settings = DB::table('settings_realtime')->first()->symbol
                            ],
                        ]);
                        $conn->send($requestObject);
                        */

                        // SEND CACHE TEST
                        // php artisan ratchet:start --param=init --param=EUR --param=USD --param="20190101 23:59:59" --param="1 D" --param="15 mins"
                        //Cache::put('webSocketObject' . env("DB_DATABASE"), 'listenCacheQue() cache called. ' . __FILE__ . ' ' . __LINE__, 5);

                    }, function (\Exception $e) use ($loop, $chart, $candleMaker) {
                        $errorString = "RatchetPawlSocket.php line 210. Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                        echo $errorString;

                        sleep(5); // Wait 5 seconds before next connection try will attempt
                        $this->handle($chart, $candleMaker); // Call the main method of this class
                        //$loop->stop();
                    });
                $loop->run();
            }
        }
    }

    private function startInit(){
        app('App\Http\Controllers\initialstart')->index(); // Moved all initial start code to a separate controller
        echo "command started with --init FLAG\n";
        event(new \App\Events\ConnectionError("Ratchet. Init start"));
        Classes\LogToFile::createTextLogFile();
        FactOrder::truncate();
        $this->initStartFlag = false;
    }

    private function startBuy(){
        echo "\n";
        $this->error('Ratchet. Test trade BUY will be placed!');
        DB::table('jobs')->where('queue', env("DB_DATABASE"))->delete(); // Empty jobs table
        Artisan::queue('ccxt:start', ['--buy' => true])->onQueue(env("DB_DATABASE"));
        $this->initStartFlag = false;
        die('Die after test trade.' . __FILE__ . " " . __LINE__);
    }

    private function startSell(){
        echo "\n";
        $this->error('Ratchet. Test trade SELL will be placed!');
        DB::table('jobs')->where('queue', env("DB_DATABASE"))->delete(); // Empty jobs table
        Artisan::queue('ccxt:start')->onQueue(env("DB_DATABASE"));
        $this->initStartFlag = false;
        die('Die after test trade.' . __FILE__ . " " . __LINE__);
    }

    /**
     * @see https://interactivebrokers.github.io/tws-api/historical_bars.html
     */
    private function listenCacheQue(){
        /* Read variables from cache and send them to open websocket connection */
        if (Cache::get('webSocketObject' . env("DB_DATABASE")) != null)
        {
            $value = Cache::get('webSocketObject' . env("DB_DATABASE"));
            $this->error($value);

            //if ($value->action == "placeOrder"){
            if (true){
                $orderObject = json_encode([
                    'clientId' => env("PUSHER_APP_ID"), // The same client id must be returned from C#. Requests from several bots cant be sent at the same time to the server.
                    'requestType' => "historyLoad",
                    'body' => [
                        //'symbol' => DB::table('settings_realtime')->first()->symbol,
                        'symbol' => $this->option('param')[1], // EUR
                        'currency' => $this->option('param')[2], // USD
                        'queryTime' => $this->option('param')[3], // 20180127 23:59:59, 20190101 23:59:59
                        'duration' => $this->option('param')[4],
                        'timeFrame' => $this->option('param')[5],
                    ]
                ]);
            }

            if ($this->connection && $orderObject) $this->connection->send($orderObject); // Send object to websocket stream
            Cache::put('webSocketObject' . env("DB_DATABASE"), null, now()->addMinute(5)); // Clear the cache. Assigned value Expires in 5 minutes
        }
    }

    private  function parseWebSocketMessage(array $message, Classes\CandleMaker $candleMaker, Classes\Chart $chart, Command $command){

        dump($message); // symbolTickPrice

        if ($message['clientId'] == env("PUSHER_APP_ID")){

            if (array_key_exists('messageType', $message)){
                if($message['messageType'] == 'SymbolTickPriceResponse'){
                    //  @param double $nojsonMessage [2][3] ($tickPrice) Price of the trade
                    //  @param integer $nojsonMessage [2][1] ($tickDate) Timestamp
                    //  @param double $nojsonMessage [2][2] ($tickVolume) Volume of the trade
                    //  @param Classes\Chart $chart Chart class instance
                    //  @param collection $settings Row of settings from DB
                    //  @param command $command variable for graphical strings output to the console
                    $candleMaker->index($message['symbolTickPrice'], $message['symbolTickTime'], 1, $chart, $this->settings, $command);
                }

            }

            if (array_key_exists('barsList', $message)){
                echo "History bars received from C#: " . count($message['barsList']) . "\n";
                Classes\History::load($message);
                //\App\Classes\PriceChannel::calculate();
            }
        }
        else
        {
            dump('The request does not belong to the instance of this bot. ' . __FILE__ . ' ' . __LINE__);
        }
    }

    // Test manual price channel method. DELETE IT!
    private  function priceChannelCalc(){
        \App\Classes\PriceChannel::calculate();
    }

    // DELETE IT!
    private function backTestStart(){
        \App\Classes\Backtest::start();
    }


}
