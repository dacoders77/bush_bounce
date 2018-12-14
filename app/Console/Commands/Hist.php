<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Hist extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hist:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'HitBtc history load daemon';

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
        // Enter dated interval from the console
        // php artisan hist:start -- symb:BTC/USD --s 12.10.2018 --e 13.10.2018 --i 15m
    }
}
