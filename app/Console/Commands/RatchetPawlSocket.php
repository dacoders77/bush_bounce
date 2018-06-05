<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Classes;

class RatchetPawlSocket extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratchet:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ratchet/pawl websocket client console application';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        // DO NOT PLACE CODE IN THE CONSTRUCTOR
        // CONSTRUCTORS ARE CALLED WHEN APPLICATION STARTS AND MY CAUSE DIFFERENT PROBLEMS
        //$chart = new Classes\Chart();
        //$this->chart = new Classes\Chart(); // New instance of Chart class
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Classes\Chart $chart, Classes\CandleMaker $candleMaker)
    {
        echo "*****Ratchet websocket console command(app) started!*****\n";
        event(new \App\Events\ConnectionError("Connection started"));
        //event(new \App\Events\BushBounce('*** Ratchet websocket console app started ***'));

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

        $connector('wss://api.bitfinex.com/ws/2', [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($chart, $candleMaker, $loop) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $chart, $candleMaker, $loop) {

                    /**
                     * If the broadcast is on - proceed events, pass it to Chart class
                     * @todo 05.26.18 This check must be performed once a second otherwise each tick will execute a requse to DB wich will overload the data base
                     *
                     */
                    if (DB::table('settings_realtime')
                            ->where('id', 1)
                            ->value('broadcast_stop') == 0)
                    {
                        /* @see http://socketo.me/api/class-Ratchet.RFC6455.Messaging.MessageInterface.html */
                        $jsonMessage = json_decode($socketMessage->getPayload(), true);
                        //print_r($jsonMessage);
                        //print_r(array_keys($z));
                        //echo $message->__toString() . "\n"; // Decode each message

                        if (array_key_exists('chanId',$jsonMessage)){
                            $chanId = $jsonMessage['chanId']; // Parsed channel ID then we are gonna listen exactly to this channel number. It changes each time you make a new connection
                        }

                        $nojsonMessage = json_decode($socketMessage->getPayload());

                        if (!array_key_exists('event',$jsonMessage)) { // All messages except first two associated arrays
                            if ($nojsonMessage[1] == "te") // Only for the messages with 'te' flag. The faster ones
                            {
                                //$chart->index($nojsonMessage, $this); // Call the method when the event is received
                                //public function index(double $tickPrice, date $tickDate, double $tickVolume, Command $command)

                                $candleMaker->index($nojsonMessage[2][3], $nojsonMessage[2][1], $nojsonMessage[2][2], $this);
                                //Classes\CandleMaker::index($nojsonMessage[2][3], $nojsonMessage[2][1], $nojsonMessage[2][2], $this);
                                //Classes\CandleMaker::index(1.0, 1, 1.0, $this);

                            }
                        }

                        /**
                         * 1. connection starts with assets list request from DB. ALWAYS
                         * 2. stop connection
                         * 3. start connection
                         *
                         *  scenario 2
                         * 1.  connection restart
                         */
                    }
                    else
                    {
                        echo "Broadcast is stopped. The flag in DB is set to false \n";
                        event(new \App\Events\ConnectionError("Broadcast stopped. " . (new \DateTime())->format('H:i:s')));
                    }


                });
                $conn->on('close', function($code = null, $reason = null) use ($chart, $candleMaker) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $this->info("line 82. connection closed");
                    $this->error("Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attpemt
                    $this->handle($chart, $candleMaker); // Call the main method of this class
                });

                //$conn->send(['event' => 'ping']);
                $z = json_encode([
                    //'event' => 'ping', // 'event' => 'ping'
                    'event' => 'subscribe',
                    'channel' => 'trades',
                    'symbol' => 'tBTCUSD'  // tBTCUSD tETHUSD tETHBTC $this->symbol $this->symbol
                ]);

                /* Multiple symbols subscription
                $x = json_encode([
                    //'event' => 'ping', // 'event' => 'ping'
                    'event' => 'subscribe',
                    'channel' => 'trades',
                    'symbol' => 'tETHUSD'  // tBTCUSD tETHUSD tETHBTC $this->symbol $this->symbol
                ]);
                */

                $conn->send($z);
                //$conn->send($x);

                /** @todo Add sleep function, for example 1 minute, after which reconnection attempt will be performed again */
            }, function(\Exception $e) use ($loop, $chart, $candleMaker) {
                $errorString = "RatchetPawlSocket.php: Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                event(new \App\Events\ConnectionError($errorString));
                sleep(5); // Wait 5 seconds before next connection try will attpemt
                $this->handle($chart, $candleMaker); // Call the main method of this class
                //$loop->stop();
            });

        $loop->run();

    }

}
