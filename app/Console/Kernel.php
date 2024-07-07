<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
         $schedule->command('app:delete-message-chanel')->everyFifteenMinutes();
         $schedule->command('app:set-date-tomorrow')->dailyAt('23:00');;
         $schedule->command('app:message3')->dailyAt('15:00');;
         $schedule->command('app:message3')->dailyAt('21:35');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
