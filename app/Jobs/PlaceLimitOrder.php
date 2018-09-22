<?php

namespace App\Jobs;

use ccxt\exmo;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class PlaceLimitOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $direction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($direction)
    {
        $this->direction = $direction;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // The body of the command

        $count = 0;
        while (true) {
            $count++;
            if ($count ==40) break;

            usleep(1000000);
            echo "PlaceLimitOrder.php Job test " . $count . " " . $this->direction . "\n<br>";

        }

    }
}
