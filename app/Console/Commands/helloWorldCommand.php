<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class helloWorldCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hello:world';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        info("called evenry minute");
        // sleep(10);
        // info("called evenry minute after");
    }
}
