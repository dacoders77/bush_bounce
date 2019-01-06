<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Classes;
use Illuminate\Support\Facades\DB;

/**
 * Request history bars from C#, store them in DB and backtest.
 * Sample command call:
 * php artisan ratchet:start --param=init --param=EUR --param=USD --param="20190101 23:59:59" --param="1 D" -param="15 mins"
 *
 * Class Backtest
 * @package App\Console\Commands
 */
class Backtest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backtest:start {--param=*}'; // Params array input

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '*** BACK TESTING PHP-C# APP *** ';

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
                    $this->error("Connection closed {$code} - {$reason}. vReconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attempt
                    $this->handle($chart, $candleMaker); // Reconnection. Call the main method of this class
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

    private function parseWebSocketMessage(array $message, Classes\CandleMaker $candleMaker, Classes\Chart $chart, Command $command, $loop){
        //dump($message); // symbolTickPrice
        if ($message['clientId'] == env("PUSHER_APP_ID")){
            if (array_key_exists('barsList', $message)){
                echo "History bars received from C#: " . count($message['barsList']) . "\n";
                Classes\History::load($message);
                \App\Classes\PriceChannel::calculate();
                \App\Classes\Backtest::start();
                $loop->stop();
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
        return $requestObject;
    }
}
