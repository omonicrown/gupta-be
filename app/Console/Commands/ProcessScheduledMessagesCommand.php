<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScheduledMessages;
use Illuminate\Console\Command;

class ProcessScheduledMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled messages due for sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled messages...');
        
        // Dispatch job to process scheduled messages
        ProcessScheduledMessages::dispatch();
        
        $this->info('Scheduled messages have been queued for processing.');
        
        return Command::SUCCESS;
    }
}