<?php

namespace App\Console\Commands;

use App\Models\Bot;
use App\Models\Setting;
use Balea\Holiday\Models\Holiday as HolidayModels;
use DiDom\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;
use Telegram\Bot\Api;

class SetDateTomorrow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-date-tomorrow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year =  convertNumber(toJalali(now(),"Y"));
        $month =  convertNumber(toJalali(now(),"m"));
        logger("get month yaer", [
            $year,
            $month
        ]);
        $client = new \GuzzleHttp\Client([
            "base_uri" => env("BASE_URL"),
            'timeout' => 6,
            'connect_timeout' => 6,
            'headers' => []]);
        $url = "https://www.time.ir/";
        $response = $client->post($url, [
            'form_params' => [
                "Year" => $year,
                "Month" => $month,
                "Base1" => 0,
                "Base2" => 1,
                "Base3" => 2,
//                "Responsive" => true,
            ],
        ]);

        $html = $response->getBody()->getContents();

        $document = new Document($html);


        $elements = $document->find('.eventHoliday ');

        $events = [];

        foreach ($elements as $i => $element) {
            $key = Str::slug(convertNumber($element->children()[1]->text()));
            $array = explode("-", $key);
            $events[] = $array[0];
        }


        logger("event",[$events]);

        $i = 1;
        $date_sh = null;
        do {

            $day = convertNumber(toJalali(now()->addDay($i), "d"));
            logger($day);
            $friday = (new Jalalian($year, $month, $day))->isFriday();
            logger("friday:".$friday);

            $thursday = (new Jalalian($year, $month, $day))->isThursday();
            logger("thursday:".$thursday);

            if(!$friday && !$thursday && !in_array($day,$events))
                $date_sh = $year . "/" . $month . "/" . $day;
            $i++;
        }while(!$date_sh);

        $date = toGregorian($date_sh, "Y/m/d");
        Setting::updateOrCreate(["key"=>"tomorrow"],[
            "value"=>$date
        ]);
        $bot_manage = Bot::where("title", "botManage")->first();
        $telegram_manage = new Api($bot_manage->token);
        $message ="تاریخ فردا تنظیم شد";
        $message .="\n";
        $message .= toJalali($date,"Y/m/d");
        $admins = $bot_manage->accessBot;
        foreach ($admins as $admin) {
            logger("data set ",[
                'chat_id' =>  $admin->user_id,
                'text' => $message,
                'parse_mode' => 'MarkdownV2'
            ]);
            $message_telegram = $telegram_manage->sendMessage(
                [
                    'chat_id' =>  $admin->user_id,
                    'text' => $message,
                ]);
        }
        cache()->forget("set_tomorrow_date");

    }
}
