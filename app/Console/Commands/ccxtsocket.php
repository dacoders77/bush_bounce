<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class ccxtsocket extends Command
{

    protected $description = 'CCXT socket app';
    public $chart;

    public static $bid = null;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ccxt:start'; // php artisan ratchet:start --init

    /**
     * The console command description.
     *
     * @var string
     */


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        // DO NOT PLACE CODE IN THE CONSTRUCTOR
        // CONSTRUCTORS ARE CALLED WHEN APPLICATION STARTS (the whole laravel!) AND MY CAUSE DIFFERENT PROBLEMS

    }


    // DELETE
    public function jpa(){

        return 100;
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        //$redis = app()->make('redis');
        //$redis->set("jo","jo");
        //dump($redis); // Output the redis object including all variables
        //echo $redis->get("jo");

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

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector('wss://api.hitbtc.com/api/2/ws', [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($loop) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $loop) {

                    // Code parse goes here
                    $message = json_decode($socketMessage->getPayload(), true);


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
                            echo $timestamp . " ";
                            echo $message['params']['bid'] . " ";
                            echo $message['params']['ask'] . "\n";

                            Redis::set('bid', $message['params']['bid']); // Assign redis key-pair value
                            Redis::set('ask', $message['params']['ask']);
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

                $z = json_encode([
                    'method' => 'subscribeTicker',
                    'params' => [
                        'symbol' => $this->settings = DB::table('settings_realtime')->first()->symbol // ETHBTC BTCUSD
                        //'symbol' => 'ethbtc' // ETHBTC ethbtc
                    ],
                    'id' => 123
                ]);





                $conn->send($z);


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
