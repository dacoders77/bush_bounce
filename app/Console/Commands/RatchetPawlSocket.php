<?php

namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Classes;
use Illuminate\Support\Facades\Log;

class RatchetPawlSocket extends Command
{
    /** @var bool $isFirstTimeBroadcastCheck First time running broadcast check. Is used only once the app is started*/
    private $isFirstTimeBroadcastCheck = true;

    /** @var bool $isFirstTimeTickCheck First tick check. Used in order to decrease quantity of ticks because pusher limit exceeds sometime*/
    private $isFirstTimeTickCheck = true;

    /** @var integer $addedTime Used in order to determine whether the broadcast is allowed or not. This check is performed once a second */
    private $addedTime = null;

    /** @var integer $addedTickTime The same but for ticks*/
    private $addedTickTime = null;

    /** @var bool $isBroadCastAllowed Flag whether to allow broadcasting or not. This flag is retrieved from the DB onece a second */
    private $isBroadCastAllowed;
    private $settings;
    /** @var bool $initStartFlag Sybolyses when the command was executed from artsan console */
    private $initStartFlag = true;


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ratchet:start {--init}'; // php artisan ratchet:start --init

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
        // DO NOT PLACE CODE IN THE CONSTRUCTOR
        // CONSTRUCTORS ARE CALLED WHEN APPLICATION STARTS (the whole laravel!) AND MY CAUSE DIFFERENT PROBLEMS
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

        Log::useDailyFiles(storage_path().'/logs/debug.log'); // Setup log name and math. Logs are created daily
        Log::debug("*****Ratchet websocket console command(app) started!*****");
        Log::debug("initial start flag from console = " . $this->option('init'));

        /**
         * Reset trade flag. If it is not reseted, it will contain previous position state.
         * Reset code is moved to a separate controller.
         * Do the reset only if the command was started from comsole, we check input option
         * in order to determine wwhether the command was started manually or caused by reconnect.
         * $this->option('init') can not be set to false that is why We use additional flag to do
         * the initial start only when started from console
         */
        if($this->option('init') && $this->initStartFlag)
        {
            app('App\Http\Controllers\initialstart')->index(); // Moved all inital start code to a separate controller
            $this->initStartFlag = false;
            echo "init strat\n";
            event(new \App\Events\ConnectionError("Ratchet. Init start line 89"));
        }


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

                    if (true) // DELETE THIS IF
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
                                /** Check whether broadcast is allowed only once a second */
                                if ($this->isFirstTimeBroadcastCheck || $nojsonMessage[2][1] >= $this->addedTime)
                                {
                                    $this->addedTime = $nojsonMessage[2][1] + 1000;
                                    $this->isFirstTimeBroadcastCheck = false;

                                    /** @var collection $settings The whole row from settings table.
                                     * Passed to CandleMaker. The reason to locate this variable here is to read this value only once a second.
                                     * We already have this functionality here - broadcast allowed check*/
                                    $this->settings = DB::table('settings_realtime')->first(); // Read settings row and pass it to CandleMaker as a parameter

                                    if (DB::table('settings_realtime')
                                            ->where('id', 1)
                                            ->value('broadcast_stop') == 0)
                                    {
                                        $this->isBroadCastAllowed = true;
                                    }
                                    else
                                    {
                                        $this->isBroadCastAllowed = false;
                                        echo "Broadcast is stopped. The flag in DB is set to false \n";
                                        event(new \App\Events\ConnectionError("Broadcast stopped. " . (new \DateTime())->format('H:i:s')));
                                    }
                                }

                                /**
                                 * 1st condition $this->isFirstTimeTickCheck - enter here only once when the app starts
                                 * 2nd tick time > computed time and broadcast is allowed
                                 */
                                if ($this->isFirstTimeTickCheck || ($nojsonMessage[2][1] >= $this->addedTickTime && $this->isBroadCastAllowed))
                                {
                                    $this->isFirstTimeTickCheck = false;
                                    $this->addedTickTime = $nojsonMessage[2][1] + $this->settings->skip_ticks_msec; // Allow ticks not frequenter than twice a second
                                    /**
                                     * @param double        $nojsonMessage[2][3] ($tickPrice) Price of the trade
                                     * @param integer       $nojsonMessage[2][1] ($tickDate) Timestamp
                                     * @param double        $nojsonMessage[2][2] ($tickVolume) Volume of the trade
                                     * @param Classes\Chart $chart Chart class instance
                                     * @param collection    $settings Row of settings from DB
                                     * @param command       $command variable for graphical strings output to the console
                                     */
                                    $candleMaker->index($nojsonMessage[2][3], $nojsonMessage[2][1], $nojsonMessage[2][2], $chart, $this->settings, $this);
                                }
                            }
                        }

                    }



                });
                $conn->on('close', function($code = null, $reason = null) use ($chart, $candleMaker) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $this->info("line 82. connection closed");
                    $this->error("Reconnecting back!");
                    Log::debug("RatchetPawlSocket.php line 181. Connection lost. Reconnecting back!");
                    sleep(5); // Wait 5 seconds before next connection try will attpemt
                    $this->handle($chart, $candleMaker); // Call the main method of this class
                });

                //$conn->send(['event' => 'ping']);
                $z = json_encode([
                    //'event' => 'ping', // 'event' => 'ping'
                    'event' => 'subscribe',
                    'channel' => 'trades',
                    'symbol' => $this->settings = DB::table('settings_realtime')->first()->symbol // tBTCUSD tETHUSD tETHBTC

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
                $errorString = "RatchetPawlSocket.php line 210. Could not connect. Reconnect in 5 sec. \n Reason: {$e->getMessage()} \n";
                echo $errorString;
                Log::debug("RatchetPawlSocket.php line 210. Could not connect. Reconnect in 5 sec");

                event(new \App\Events\ConnectionError($errorString));
                sleep(5); // Wait 5 seconds before next connection try will attpemt
                $this->handle($chart, $candleMaker); // Call the main method of this class
                //$loop->stop();
            });

        $loop->run();

    }

}
