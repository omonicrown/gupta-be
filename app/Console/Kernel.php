<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('hello:world')->everyMinute();
        $schedule->command('subscription:check')->dailyAt('02:16');
        $schedule->command('notification:push');
        $schedule->command('email:send-links')->daily();
        $schedule->command('send:monthly-sales-summary')->monthly();

         // Process scheduled messages every minute
         $schedule->command('messages:process-scheduled')->everyMinute();
        
         // Check for messages that need delivery status updates every 15 minutes
         $schedule->command('messages:check-delivery-status')->everyFifteenMinutes();
         
         // Prune old records and database maintenance
         $schedule->command('telescope:prune --hours=48')->daily();
         $schedule->command('queue:prune-failed --hours=48')->daily();
         
         // Daily database backups
         $schedule->command('backup:run')->dailyAt('02:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
