<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Classes;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Routing\Tests\Matcher\DumpedUrlMatcherTest;
use App\Classes\WsApiMessages\PusherApiMessage;

// 1. real-time command is started
// 2. currency, symbol is provided
// 3. time frame is entered
// 4. time frame value is updated in DB
// 5. when historyLoad method is performed, there are two types of value: 1 min and >1min -> 1 mins, 2 mins, 3 mins etc.


/**
 * Send a real-time trades subscription request to C#.
 * Load history bars and subscribe to ticks:
 * php artisan realtime:start --param=init --param=AAPL --param=USD --param="15 mins" --param=NONE
 * Test trade (no history or subscription):
 * php artisan realtime:start --param=init --param=AAPL --param=USD --param="1 min" --param=BUY
 *
 *
 * $this->option('param')[0] - init start (reserved)
 * $this->option('param')[1] - symbol
 * $this->option('param')[2] - currency
 * $this->option('param')[3] - time frame
 * ---
 * $this->option('param')[4] - BUY/SELL place market order test. Used when have no time to wait and need to test a trade
 * $this->option('param')[5] - order volume
 *
 * Class RealTime
 * @package App\Console\Commands
 */
class RealTime extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'realtime:start {--param=*}'; // Params array input

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '*** Real-time subscription PHP-C# APP ***';

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
     * @param Classes\Chart $chart
     * @param Classes\CandleMaker $candleMaker
     */
    public function handle(Classes\Chart $chart, Classes\CandleMaker $candleMaker)
    {
        // dump($this->option('param')[3]);
        // die('RealTime.php');
        // $this->placeTestOrder->PlaceMarketOrder()
        echo "Params: = ";
        dump($this->option('param'));
        DB::table(env("ASSET_TABLE"))->truncate();
        $this->settings = DB::table('settings_realtime')->first();

        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, [
            'dns' => '8.8.8.8',
            'timeout' => 10
        ]);

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);
        $connector("ws://localhost:8181", [], ['Origin' => 'http://localhost'])
            ->then(function (\Ratchet\Client\WebSocket $conn) use ($chart, $candleMaker, $loop) {

                $conn->on('message', function (\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $chart, $candleMaker, $loop) {
                    $nojsonMessage = json_decode($socketMessage->getPayload(), true);
                    if ($nojsonMessage) $this->parseWebSocketMessage($nojsonMessage, $candleMaker, $chart, $this, $loop);
                });

                $conn->on('close', function ($code = null, $reason = null) use ($chart, $candleMaker) {
                    $this->error("Connection closed {$code} - {$reason}. vReconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    $this->handle($chart, $candleMaker); // Call the main method of this class
                });

                /**
                 * When console command has these two params at the end: --param=SELL --param=22
                 * It means that a test trade should be executed.
                 * History load and real-time subscriptions - will not.
                 */
                if ($this->option('param')[4] == 'BUY' || $this->option('param')[4] == 'SELL') {
                    $conn->send($this->placeTestOrder($this->option('param')[1], $this->option('param')[4], $this->option('param')[5]));
                    // die('die from RealTime.php');
                }

                if ($this->option('param')[4] == 'NONE') {
                    $conn->send($this->historyLoad()); // Request history bars and store them in DB
                    $conn->send($this->subscribeToSymbol()); // Subscribe to ticks
                    // die('die from RealTime.php SUBSCRIPTION');
                }

                if ($this->option('param')[4] == 'HIST') {
                    $conn->send($this->historyLoad());
                    // die('die from RealTime.php SUBSCRIPTION');
                }

                //$conn->send($this->historyLoad()); // Request history bars and store them in DB
                //$conn->send($this->subscribeToSymbol()); // Subscribe to ticks

            }, function (\Exception $e) use ($loop, $chart, $candleMaker) {
                $errorString = "RatchetPawlSocket.php. Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                sleep(5); // Wait 5 seconds before next connection try will attempt
                $this->handle($chart, $candleMaker); // Call the main method again
            });
        $loop->run();
    }

    /**
     * Receive and parse two types of messages:
     * 1. Symbol tick
     * 2. Symbol tick error
     *
     * @param array $message
     * @param Classes\CandleMaker $candleMaker
     * @param Classes\Chart $chart
     * @param Command $command
     * @param $loop
     */
    private function parseWebSocketMessage(array $message, Classes\CandleMaker $candleMaker, Classes\Chart $chart, Command $command, $loop){
        //if ($message['clientId'] == env("PUSHER_APP_ID")){ // 547841
        if (true){
            if (array_key_exists('messageType', $message)){
                if($message['messageType'] == 'SymbolTickPriceResponse'){
                    /**
                     * @param double $nojsonMessage [2][3] ($tickPrice) Price of the trade
                     * @param integer $nojsonMessage [2][1] ($tickDate) Timestamp
                     * @param double $nojsonMessage [2][2] ($tickVolume) Volume of the trade
                     * @param Classes\Chart $chart Chart class instance
                     * @param collection $settings Row of settings from DB
                     * @param command $command variable for graphical strings output to the console
                     */
                    $candleMaker->index($message['symbolTickPrice'], $message['symbolTickTime'], 1, $chart, $this->settings, $command);
                }
                if($message['messageType'] == 'Error'){
                    $this->error('Error');
                    dump($message);
                    $pusherApiMessage = new Classes\WsApiMessages\PusherApiMessage();
                    $pusherApiMessage->clientId = 12345;
                    $pusherApiMessage->messageType = 'error'; // symbolTickPriceResponse, error, info etc.
                    $pusherApiMessage->payload = $message['errorText'];
                    event(new \App\Events\BushBounce($pusherApiMessage->toArray()));
                }
                if($message['messageType'] == 'Info'){
                    $this->error('Info');
                    dump($message);
                    $pusherApiMessage = new Classes\WsApiMessages\PusherApiMessage();
                    $pusherApiMessage->clientId = 12345;
                    $pusherApiMessage->messageType = 'info';
                    $pusherApiMessage->payload = $message['infoText'];
                    event(new \App\Events\BushBounce($pusherApiMessage->toArray()));
                }

                if (array_key_exists('barsList', $message)){
                    echo "History bars received from C#: " . count($message['barsList']) . "\n";
                    Classes\History::load($message);
                    \App\Classes\PriceChannel::calculate();
                    //\App\Classes\Backtest::start();
                    //$loop->stop();

                    //$messageArray['serverInitialStart'] = true; // Reload the whole chart after the bar list is received
                    //event(new \App\Events\BushBounce(['messageType' => 'reloadChart'])); // Event is received in Chart.vue

                    $pusherApiMessage = new PusherApiMessage();
                    $pusherApiMessage->clientId = 12345;
                    $pusherApiMessage->messageType = 'reloadChartAfterHistoryLoaded';
                    $pusherApiMessage->payload = null;
                    event(new \App\Events\BushBounce($pusherApiMessage->toArray()));
                }
            }
        }
        else
        {
            dump('The request does not belong to the instance of this bot. ' . __FILE__ . ' ' . __LINE__);
        }
    }

    private function subscribeToSymbol(){
        $requestObject = json_encode([
            'clientId' => env("PUSHER_APP_ID"), // The same client id must be returned from C#. Requests from several bots cant be sent at the same time to the server.
            'requestType' => "subscribeToSymbol",
            'body' => [
                //'symbol' => DB::table('settings_realtime')->first()->symbol,
                'symbol' => $this->option('param')[1], // EUR AAPL
                'currency' => $this->option('param')[2], // USD
                'queryTime' => null,
                'duration' => null,
                'timeFrame' => null,
            ]
        ]);
        return $requestObject;
    }

    /**
     * Load necessary amount of bars by the time the real-time trading is started.
     * These bars are needed for plotting the chart and price channel.
     * By default 3600 seconds of history bars is requested.
     * If 1 day is needed: 'duration' => '1 D'
     *
     * @see https://interactivebrokers.github.io/tws-api/historical_bars.html
     * @return string
     */
    private function historyLoad(){
        $arr = explode(" ", $this->option('param')[3], 2); // Get time digits out of time frame string
        DB::table('settings_realtime')->where('id', 1)->update(['time_frame' => $arr[0],]); // Stare in DB
        $requestObject = json_encode([
            'clientId' => env("PUSHER_APP_ID"), // The same client id must be returned from C#. Requests from several bots cant be sent at the same time to the server.
            'requestType' => "historyLoad",
            'body' => [
                'symbol' => $this->option('param')[1], // EUR
                'currency' => $this->option('param')[2], // USD
                'queryTime' => null, // 20180127 23:59:59, 20190101 23:59:59
                'duration' => '3600 S', // '3600 S' '1 D'
                'timeFrame' => $this->option('param')[3], // 1 min, 15 mins
            ]
        ]);
        return $requestObject;
    }

    private function placeTestOrder($symbol, $direction, $volume){
        // $arr = explode(" ", $this->option('param')[3], 2); // Get time digits out of time frame string
        // DB::table('settings_realtime')->where('id', 1)->update(['time_frame' => $arr[0],]); // Stare in DB
        $requestObject = json_encode([
            'clientId' => env("PUSHER_APP_ID"), // The same client id must be returned from C#. Requests from several bots cant be sent at the same time to the server.
            'requestType' => "placeOrder", // historyLoad
            'body' => [
                'symbol' => $symbol, // EUR AAPL
                'currency' => 'USD', // USD
                'direction' => $direction,
                'volume' => $volume
            ]
        ]);
        return $requestObject;
    }

}
