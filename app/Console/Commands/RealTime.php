<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Classes;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Routing\Tests\Matcher\DumpedUrlMatcherTest;

/**
 * Send a real-time trades subscription request to C#.
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

                $conn->send($this->requestObject());

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
        //dump($message); // symbolTickPrice
        if ($message['clientId'] == env("PUSHER_APP_ID")){
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
                // TICK ERROR MESSAG!
                if($message['messageType'] == 'Error'){
                    $this->error('Error');
                    dump($message);
                    // send pusher message
                }
            }
        }
        else
        {
            dump('The request does not belong to the instance of this bot. ' . __FILE__ . ' ' . __LINE__);
        }
    }

    private function requestObject(){
        $requestObject = json_encode([
            'clientId' => env("PUSHER_APP_ID"), // The same client id must be returned from C#. Requests from several bots cant be sent at the same time to the server.
            'requestType' => "subscribeToSymbol",
            'body' => [
                //'symbol' => DB::table('settings_realtime')->first()->symbol,
                'symbol' => $this->option('param')[1], // EUR
                'currency' => $this->option('param')[2], // USD
                //'queryTime' => $this->option('param')[3], // 20180127 23:59:59, 20190101 23:59:59
                //'duration' => $this->option('param')[4],
                //'timeFrame' => $this->option('param')[5],
            ]
        ]);
        return $requestObject;
    }
}
