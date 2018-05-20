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
    public function handle(Classes\Chart $chart)
    {
        echo "*****Ratchet websocket console command(app) started!*****\n";
        //event(new \App\Events\BushBounce('*** Ratchet websocket console app started ***'));


        // Code from: https://github.com/ratchetphp/Pawl
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, [
            'dns' => '8.8.8.8', // Does not work through OKADO internet provider. Timeout error
            'timeout' => 10
        ]);

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector('wss://api.bitfinex.com/ws/2', [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) use ($chart, $loop) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn, $chart, $loop) {
                    $chart->index($socketMessage, $this); // Call the method when the event is received

                    /** Stop the broadcast */
                    if (DB::table('settings_realtime')
                            ->where('id', 1)
                            ->value('broadcast_stop') == 1)
                    {
                        DB::table("settings_realtime")
                            ->where('id', 1) // id of the last record. desc - descent order
                            ->update([
                                'broadcast_stop' => 0
                            ]);
                        $this->alert('The broadcast is being stopped!');
                        $loop->stop();
                    }


                });
                $conn->on('close', function($code = null, $reason = null) use ($chart) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $this->info("line 82. connection closed");
                    $this->error("Reconnecting back!");
                    $this->handle($chart);
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

                /** @todo Add sleep function, for example 1 minute, after which recconection attempt will be performed */
            }, function(\Exception $e) use ($loop) {
                echo "RatchetPawlSocket.php: Could not connect: \n {$e->getMessage()}\n";
                $loop->stop();
            });
        $loop->run();

    }

}
