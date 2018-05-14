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
        //$chart = new Classes\Chart();
        $this->chart = new Classes\Chart();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo "*****Ratchet websocket console command(app) started!*****\n";

        /*
        $this->timeFrame =
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('time_frame');

        // Get traded symbol from DB. String must look like: tBTCUSD
        // MAKE IT UPPER CASE!
        $this->symbol = "t" .
            DB::table('settings_realtime')
                ->where('id', env("SETTING_ID"))
                ->value('symbol');
        */

        // Code from: https://github.com/ratchetphp/Pawl
        $loop = \React\EventLoop\Factory::create();
        $reactConnector = new \React\Socket\Connector($loop, [
            'dns' => '8.8.8.8', // Does not work through OKADO internet provider. Timeout error
            'timeout' => 10
        ]);

        $connector = new \Ratchet\Client\Connector($loop, $reactConnector);

        $connector('wss://api.bitfinex.com/ws/2', [], ['Origin' => 'http://localhost'])
            ->then(function(\Ratchet\Client\WebSocket $conn) {
                $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $socketMessage) use ($conn) {
                    //RatchetWebSocket::out($socketMessage); // Call the function when the event is received
                    //Classes\Chart::index($socketMessage);

                    //$this->chart->index($socketMessage); // Call the method when the event is received
                    echo $socketMessage;

                });
                $conn->on('close', function($code = null, $reason = null) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $this->error("line 67. connection closed");
                    $this->error("Reconnecting back!");
                    $this->handle();
                });

                //$conn->send(['event' => 'ping']);
                $z = json_encode([
                    //'event' => 'ping', // 'event' => 'ping'
                    'event' => 'subscribe',
                    'channel' => 'trades',
                    'symbol' => 'tBTCUSD'  // tBTCUSD tETHUSD tETHBTC $this->symbol $this->symbol
                ]);

                $x = json_encode([
                    //'event' => 'ping', // 'event' => 'ping'
                    'event' => 'subscribe',
                    'channel' => 'trades',
                    'symbol' => 'tETHUSD'  // tBTCUSD tETHUSD tETHBTC $this->symbol $this->symbol
                ]);

                $conn->send($z);
                $conn->send($x);
            }, function(\Exception $e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });
        $loop->run();
    }

}
