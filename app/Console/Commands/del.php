<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class del extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'del:start';

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
        dump('del');
        // Cache::put('webSocketObject' . env("DB_DATABASE"), 'websocket object', 5);
        Cache::put('webSocketObject' . env("DB_DATABASE"), json_encode(['symbol' => 'EUR', 'currency' => 'USD', 'direction' => 'SELL', 'volume' => 1]), 5);

    }
}
