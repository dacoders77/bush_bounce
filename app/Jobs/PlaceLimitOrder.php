<?php

namespace App\Jobs;

use ccxt\exmo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Classes;
use Illuminate\Support\Facades\Redis;

class PlaceLimitOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $direction;
    private $exchange;
    public $timeout = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->direction = $direction;

        // Create an instance of exchange class
        //$this->exchange = new Classes\Hitbtc\HitBtcPhp($_ENV['HITBTC_PUBLIC_API_KEY'], $_ENV['HITBTC_PRIVATE_API_KEY']);

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $counter = 0;
        for ($i = 0; $counter < 5; $counter++){
            dump('the THREAD! ---' . $counter);
            usleep(1000000);
        }
        return;
    }
}
