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
        $schedule->command('app:set-date-tomorrow')->dailyAt('07:00');
        $time_message_none = env("NONE_HOUR", "15") . ":" . env("NONE_MIN", "30");
        $schedule->command('app:message3')->dailyAt($time_message_none);
        $parameter = cache()->remember("parameter_need", now()->setTime(23, 59), function () {
            return Setting::whereIn("key", ["start_hours_of_operation", "end_hours_of_operation"])->get()->keyBy("key");
        });
        $array_time_s = explode(":", data_get($parameter, "start_hours_of_operation.value", "09:00"));
        $array_time_e = explode(":", data_get($parameter, "end_hours_of_operation.value", "22:00"));
        if ($array_time_s) {
            $start_time = Carbon::createFromTime(data_get($array_time_s, 0), data_get($array_time_s, 1), 0)->format("H:i");
            $schedule->command('app:message-start')->dailyAt($start_time);
        }
        if ($array_time_e) {
            $end_time = Carbon::createFromTime(data_get($array_time_e, 0), data_get($array_time_e, 1), 0)->format("H:i");

            $schedule->command('app:message-end')->dailyAt($end_time);
        }

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
