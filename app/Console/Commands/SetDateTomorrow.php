<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Balea\Holiday\Models\Holiday as HolidayModels;
use DiDom\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Morilog\Jalali\Jalalian;

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
            $events[$array[0]] = trim($element->children()[2]->text());
        }
        $elements = $document->find('.holiday');


        foreach ($elements as $i => $element) {
            $days = [];
            foreach ($element->children()[0]->children() as $day)
                $days[] = Str::slug(convertNumber($day->text()));
        }
        $i = 1;
        do {
            $day = convertNumber(toJalali(now()->addDay($i), "d"));
            $date_sh = $year . "/" . $month . "/" . $day;
            $i++;
        }while(!in_array($day,$days));
        $date = toGregorian($date_sh, "Y/m/d");
        Setting::updateOrCreate(["key"=>"tomorrow"],[
            "value"=>$date
        ]);

    }
}
