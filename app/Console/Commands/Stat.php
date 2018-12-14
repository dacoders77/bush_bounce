<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ccxt\hitbtc2;
use App\FactOrder; // Model link
use Illuminate\Support\Facades\DB;

class Stat extends Command
{
    private $profit;
    private $buys = array();
    private $sells = array();

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    // protected $signature = "stat:start {--from=2018-12-08 07:28:40} {--till=2018-12-08 07:29:54}";
    protected $signature = "stat:start {--from=} {--till=}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle()
    {
        $priceStep = DB::table('settings_realtime')->first()->price_step;

        $this->exchange = new hitbtc2(); // hitbtc()
        $this->exchange->apiKey = $_ENV['HITBTC_PUBLIC_API_KEY'] ;
        $this->exchange->secret = $_ENV['HITBTC_PRIVATE_API_KEY'];

        $response = $this->exchange->privateGetHistoryTrades([
            'symbol' => 'ETHUSD',
            'by' => 'timestamp',
            'from' =>strtotime($this->option('from')) * 1000,
            'till' =>strtotime($this->option('till')) * 1000
        ]);
        dump($response);
        FactOrder::truncate();

        foreach ($response as $order){
            FactOrder::create([
                'trade_id' => $order['id'],
                'client_order_id' => $order['clientOrderId'],
                'order_id' => $order['orderId'],
                'symbol' => $order['symbol'],
                'side' => $order['side'],
                'quantity' => $order['quantity'],
                'price' => $order['price'],
                'fee' => $order['fee'],
                'time' => $order['timestamp']
            ]);
        }

        $ordersArray = [
            ['side' => 'buy', 'quantity' => 2, 'price' => 4],
            ['side' => 'buy', 'quantity' => 2, 'price' => 5],
            ['side' => 'sell', 'quantity' => 3, 'price' => 6]
        ];

        //foreach ($ordersArray as $order) {
        foreach ($response as $order) {
            if($order['side'] == 'buy'){
                $x = 0;
                do {
                    array_push($this->buys, $order['price']);
                    $x++;
                } while ($x < $order['quantity'] / $priceStep);
            }
            else{
                $x = 0;
                do {
                    array_push($this->sells, $order['price']);
                    $x++;
                } while ($x < $order['quantity'] / $priceStep);
            }
        }

        $x = 0;
        do {
            $this->profit += ($this->sells[$x] - $this->buys[$x]) * $priceStep;
            //echo $this->sells[$x] . " - " . $this->buys[$x] . "\n";

            $x++;
            // Compare arrays. Which one is smaller - use that one for for each
        } while ($x < (count($this->buys) > count($this->sells) ? count($this->sells) : count($this->buys)));

        dump($this->profit);

    }
}
