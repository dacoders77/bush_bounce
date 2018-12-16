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
    private $from;
    private $till;
    private $response;
    private $fee;

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
        Dump('Entered stat. ' . __FILE__);
        //FactOrder::truncate();

        $priceStep = DB::table('settings_realtime')->first()->price_step;

        $this->exchange = new hitbtc2(); // hitbtc()
        $this->exchange->apiKey = $_ENV['HITBTC_PUBLIC_API_KEY'] ;
        $this->exchange->secret = $_ENV['HITBTC_PRIVATE_API_KEY'];


        /* True if from and till vars not inputted from console.
         * False if - inputted
         */

        // Not inputted
        if(!$this->option('from')){
            // If orders table is not empty
            if(!DB::table('orders')->get()->isEmpty()){

                dump(DB::table('fact_orders')->get()->isEmpty());

                // fact_orders table is empty -> use date from orders table
                if(DB::table('fact_orders')->get()->isEmpty()){
                    $this->from = DB::table('orders')
                        ->first()->order_time;
                }

                // fact_orders table is not empty -> take the last row from fact_orders table
                if(!DB::table('fact_orders')->get()->isEmpty()){
                    $this->from = DB::table('fact_orders')
                        ->orderBy('id', 'desc')
                        ->first()->time;
                }
            }

        }
        // Inputted
        else{
            $this->from = strtotime($this->option('from')) * 1000;
            $this->till = strtotime($this->option('till')) * 1000;
        }

        dump($this->from);


        // When param date is not inputted and orders table is empty.
        // No trades has been opend yet.
        if ($this->from)
            // Case 1: fact_orders is empty
            // - use $this->form as a start date
            // Case 2: fact_orders in NOT empty
            // - use last date from fact_orders

        $this->response = $this->exchange->privateGetHistoryTrades([
            'symbol' => DB::table('settings_realtime')->first()->symbol,
            'by' => 'timestamp',
            'from' => strtotime($this->from) * 1000 + 1000,
            'till' => $this->till
        ]);

        dump($this->response);


        if($this->response){
            foreach (array_reverse($this->response) as $order){
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

            /* Testing array. Can be thrown to foreach below.
            $ordersArray = [
                ['side' => 'buy', 'quantity' => 2, 'price' => 4],
                ['side' => 'buy', 'quantity' => 2, 'price' => 5],
                ['side' => 'sell', 'quantity' => 3, 'price' => 6]
            ];
            */

            foreach ($this->response as $order) {
            //foreach (FactOrder::all()->toArray() as $order) { // Take trades from fact orders without a request
                if($order['side'] == 'buy'){
                    $x = 0;
                    do {
                        array_push($this->buys, ['price' => $order['price'], 'fee' => $order['fee']]);
                        $x++;
                    } while ($x < $order['quantity'] / $priceStep);
                }
                else{
                    $x = 0;
                    do {
                        array_push($this->sells, ['price' => $order['price'], 'fee' => $order['fee']]);
                        $x++;
                    } while ($x < $order['quantity'] / $priceStep);
                }
                $this->fee += $order['fee'];
            }

            /**
             * If both arrays are not empty.
             * If one is empty - only buy or sell trade has just occurred.
             * Trade is not closed. Profit can not yet be calculated.
             */
            if($this->buys && $this->sells){
                $x = 0;
                do {
                    $this->profit += ($this->sells[$x]['price'] - $this->buys[$x]['price']) * $priceStep; // Accumulate profit
                    //$this->fee += (($this->sells[$x]['fee'] + $this->buys[$x]['fee'])) * $priceStep * 10;
                    $x++;
                    // Compare arrays. Which one is smaller - use that one for foreach
                } while ($x < (count($this->buys) > count($this->sells) ? count($this->sells) : count($this->buys)));
            }
            else{
                $this->error('Only first trade in fact_orders is present which is not yet closed. Profit can not be calculated.');
            }

            // Update profit column
            $priceStep = DB::table('settings_realtime')->first()->price_step;
            FactOrder::where('id', '!=', null)
                ->orderBy('id', 'desc')
                ->first()
                ->update([
                    'profit' => $this->profit,
                    'net_profit' => $this->fee
                ]);

            dump($this->profit);
        }



    }
}
