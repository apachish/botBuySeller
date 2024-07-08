<?php

namespace App\Console;

use App\Models\Setting;
use Carbon\Carbon;
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
         $schedule->command('app:set-date-tomorrow')->dailyAt('00:10');
         $schedule->command('app:message3')->dailyAt('15:00');
        $parameter = cache()->remember("parameter_need", now()->setTime(23, 59), function () {
            return Setting::whereIn("key", ["start_hours_of_operation", "end_hours_of_operation"])->get()->keyBy("key");
        });
        $array_time_s = explode(":", data_get($parameter, "start_hours_of_operation.value", "09:00"));
        $array_time_e = explode(":", data_get($parameter, "end_hours_of_operation.value", "22:00"));
        $start_time = Carbon::createFromTime(data_get($array_time_s, 0), data_get($array_time_s, 1), 0)->subMinute("15");
        logger("start_time",[$start_time]);
        $schedule->command('app:message3')->dailyAt($start_time);
        $end_time = Carbon::createFromTime(data_get($array_time_e, 0), data_get($array_time_e, 1), 0)->subMinute("15");
        logger("end_time",[$start_time]);

        $schedule->command('app:message3')->dailyAt($end_time);

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
