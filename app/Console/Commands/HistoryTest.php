<?php

namespace App\Console\Commands;

//use App\Events\TaskEvent; // For events test
use App\Classes;
use Illuminate\Console\Command;

class HistoryTest extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'history:test';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Command description';

    private $z;
    /**
     * Create a new command instance.
     * @return void
     */
    public function __construct()
    {

        parent::__construct();
    }



    /**
     * Execute the console command.
     * @return mixed
     */
    public function handle()
    {
        $chart = new Classes\Chart();
        $noJsonMessage = array(
            0 => null,
            1 => null,
            2 => [
                0 => null,
                1 => 1527447480000, // timestamp
                2 => null, // vol
                3 => 7282.4 //price
            ]
        );

        while (true){

            $chart->hist();
            $chart->index($noJsonMessage, $this);
            $this->error("WARNING!!");
            //echo $this->z;
            echo "zzz " . $this->z . "\n";

            sleep(1);
        }
    }
}
