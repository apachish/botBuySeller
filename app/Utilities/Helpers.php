<?php

use App\Models\Setting;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\File;
use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;
use Propaganistas\LaravelPhone\PhoneNumber;
use Armanbroker\Structure\Score\Models\Cost;
use Armanbroker\Structure\Setting\Models\Definition;

if (!function_exists('isValidShamsiDate')) {

    function isValidShamsiDate($date)
    {
        // الگوی regex برای اعتبارسنجی فرمت تاریخ به صورت yyyy/mm/dd
        $pattern = '/^(13|14)\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12][0-9]|3[01])$/';

        // بررسی مطابقت تاریخ با الگو
        if (!preg_match($pattern, $date)) {
            return false;
        }

        // جداسازی سال، ماه و روز از تاریخ
        list($year, $month, $day) = explode('/', $date);

        // تبدیل رشته به عدد صحیح
        $year = (int)$year;
        $month = (int)$month;
        $day = (int)$day;

        // بررسی منطقی بودن تاریخ شمسی
        return checkShamsiDate($year, $month, $day);
    }
}
if (!function_exists('checkShamsiDate')) {

    function checkShamsiDate($year, $month, $day)
    {
        // تعداد روزهای هر ماه در تقویم شمسی
        $daysInMonth = array(
            1 => 31, 2 => 31, 3 => 31, 4 => 31, 5 => 31, 6 => 31,
            7 => 30, 8 => 30, 9 => 30, 10 => 30, 11 => 30, 12 => 29
        );

        // بررسی سال کبیسه در تقویم شمسی
        if ($month == 12 && isLeapYear($year)) {
            $daysInMonth[12] = 30;
        }

        // بررسی منطقی بودن روز در ماه
        if ($day > 0 && $day <= $daysInMonth[$month]) {
            return true;
        }

        return false;
    }
}
if (!function_exists('isLeapYear')) {

    function isLeapYear($year)
    {
        // بررسی سال کبیسه در تقویم شمسی
        $mod = $year % 33;
        return ($mod == 1 || $mod == 5 || $mod == 9 || $mod == 13 || $mod == 17 || $mod == 22 || $mod == 26 || $mod == 30);
    }
}

if (!function_exists('makeDirectoryStorage')) {
    function makeDirectoryStorage($path)
    {
        $array_path = array_filter(explode("/", $path));
        $base_path = "";
        foreach ($array_path as $create_path) {
            $base_path .= "/" . $create_path;
            if (!is_dir(storage_path($base_path))) {
                File::makeDirectory(storage_path($base_path), $mode = 0777, true, true);

            }
        }
    }
}

if (!function_exists('getPriceFormat')) {
    function getPriceFormat($money)
    {
        return number_format($money, 0);

    }
}

if (!function_exists('isValidTime')) {
    function isValidTime($time)
    {
        $pattern = '/^([01]\d|2[0-3]):([0-5]\d)$/';
        return preg_match($pattern, $time);
    }

}
if (!function_exists('cleanInput')) {
    function cleanInput($input)
    {
        // استفاده از یک الگو برای جدا کردن بخش‌ها
// 1       $pattern = '/^(\d+)\s*(خفن|خفش|ففش|ففن|خفپ|ففپ|خفم|ففم|فف|خف|فپ|خپ|فم|خم|خش|فش|خن|فن|خ|ف)\s*(\d?)(:\s*(.*))?$/u';
        $input = trim($input);
        $pattern = "/^(\d{3}|\d{5})\s*((?:خف|فف|فپ|فم|خم|خش|فش|خن|فن|خ|ف)\s*){1,3}([1-3]?)\s*(:.*)?$/u";
        if (preg_match($pattern, $input, $matches)) {
            $e = explode(":", $input);
            $a = str_replace(" ", "", data_get($e, 0));
            return $input = $a . (data_get($e, 1) ? ":" . data_get($e, 1) : null);

//            if (preg_match($pattern, $input, $matches)) {
//                // حذف فضای خالی از بخش‌های مورد نیاز
//                $number = preg_replace('/\s+/', '', $matches[1]);
//                $letters = preg_replace('/\s+/', '', $matches[2]);
//                $optional_number = preg_replace('/\s+/', '', $matches[3]);
//                $comment = isset($matches[5]) ? $matches[5] : '';
//
//                // ساختن رشته نهایی
//                $cleanedInput = $number . $letters . $optional_number;
//                if (!empty($comment)) {
//                    $cleanedInput .= ':' . $comment;
//                }
//            }
//            return $cleanedInput;
        }
        return $input; // اگر الگو تطابق نداشت، همان ورودی را برگردانید
    }
}
if (!function_exists('getPriceFormat')) {
    function getPriceFormat($money)
    {
        return number_format($money, 0);

    }
}

if (!function_exists('getTypeTitleOrder')) {
    function getTypeTitleOrder($type)
    {
        if (in_array($type, ["خفپ", "خفم", "خفش", "خفن", "خش", "خن", "خم", "خف", "خپ", "خ"]))
            return "خرید";
        elseif (in_array($type, ["ففپ", "ففم", "ففش", "ففن", "فش", "فن", "فم", "فف", "فپ", "ف"]))
            return "فروش";
    }
}
if (!function_exists('getTypeOrder')) {
    function getTypeOrder($type)
    {
        if (in_array($type, ["خفپ", "خفم", "خفش", "خفن", "خش", "خن", "خم", "خف", "خپ", "خ"]))
            return "buy";
        elseif (in_array($type, ["ففپ", "ففم", "ففش", "ففن", "فش", "فن", "فم", "فف", "فپ", "ف"]))
            return "sell";
    }
}

if (!function_exists('generateUniqueSixDigitCode')) {
    function generateUniqueSixDigitCode()
    {
        // دریافت زمان فعلی بر حسب میلی‌ثانیه
        $microtime = microtime(true);

        // تبدیل زمان به رشته
        $microtimeString = str_replace('.', '', $microtime);

        // تبدیل رشته به عدد و سپس به عدد شش رقمی
        $sixDigitCode = substr($microtimeString, -6);

        return $sixDigitCode;
    }
}

if (!function_exists('getTypeTransfer')) {
    function getTypeTransfer($type)
    {
        $time = Carbon::now();

        cache()->forget("forbidden_day");
        $forbidden_day = cache()->remember("forbidden_day", now()->setTime(23, 59), function () {
            $item = Setting::where("key", "forbidden_day")->first();
            return data_get($item, "value");
        });
        if (!$forbidden_day)
            $forbidden_day = $time->isThursday() || $time->isFriday() ? true : false;
        $morning = Carbon::create($time->year, $time->month, $time->day, 8, 0, 0); //set time to 08:00
        $none = Carbon::create($time->year, $time->month, $time->day, env("NONE_HOUR", "15"), env("NONE_MIN", "30"), 0); //set time to 18:00
        $none_13_30 = Carbon::create($time->year, $time->month, $time->day, env("NONE_M_HOUR", "13"), env("NONE_M_MIN", "30"), 0); //set time to 18:00
        if($forbidden_day){
            if (in_array($type, ["خ", "ف","فف", "خف"]))
                return "با حواله عادی";
            elseif (in_array($type, ["خش", "فش","خفش", "ففش"]))
                return "شنا";
            elseif (in_array($type, ["خشط", "فشط","خفشط", "ففشط"]))
                return "شنا شرطی";
            elseif (in_array($type, ["خن", "فن","خفن", "ففن"]))
                return "نقدی";
            elseif (in_array($type, ["فم", "خم", "فپ", "خپ","ففم", "خفم", "ففپ", "خفپ"]))
                return "معکوس";
            elseif (in_array($type, ["فمط", "خمط", "فپط", "خپط","ففمط", "خفمط", "ففپط", "خفپط"]))
                return "معکوس شرطی";
        }else{
            if ($time->between($morning, $none, true) && in_array($type, ["خ", "ف"]))
                return "عادی روز";
            elseif (in_array($type, ["خ", "ف","فف", "خف"]))
                return "با حواله عادی";
            elseif ($time->between($morning, $none_13_30, true) && in_array($type, ["خش", "فش"]))
                return "شنا روز";
            elseif (in_array($type, ["خش", "فش","خفش", "ففش"]))
                return "شنا";
            elseif ($time->between($morning, $none_13_30, true) && in_array($type, ["خشط", "فشط"]))
                return " شنا روز شرطی";
            elseif (in_array($type, ["خشط", "فشط","خفشط", "ففشط"]))
                return "شنا شرطی";
            elseif ($time->between($morning, $none, true) && in_array($type, ["خن", "فن"]))
                return "نقدی حاضر";
            elseif (in_array($type, ["خفن", "ففن","خن", "فن"]))
                return "نقدی";
            elseif ( $time->between($morning, $none_13_30, true) && in_array($type, ["فم", "خم", "فپ", "خپ"]))
                return "معکوس روز";
            elseif (in_array($type, ["ففم", "خفم", "ففپ", "خفپ","فم", "خم", "فپ", "خپ"]))
                return "معکوس";
            elseif ( $time->between($morning, $none_13_30, true) && in_array($type, ["فمط", "خمط", "فپط", "خپط"]))
                return "معکوس روز شرطی";
            elseif (in_array($type, ["ففمط", "خفمط", "ففپط", "خفپط","فمط", "خمط", "فپط", "خپط"]))
                return "معکوس شرطی";
        }


    }
}


if (!function_exists('getTypeSimilar')) {
    function getTypeSimilar($type)
    {
        $time = Carbon::now();

        cache()->forget("forbidden_day");
        $forbidden_day = cache()->remember("forbidden_day", now()->setTime(23, 59), function () {
            $item = Setting::where("key", "forbidden_day")->first();
            return data_get($item, "value");
        });
        if (!$forbidden_day)
            $forbidden_day = $time->isThursday() || $time->isFriday() ? true : false;
        $morning = Carbon::create($time->year, $time->month, $time->day, 9, 0, 0); //set time to 08:00
        $none = Carbon::create($time->year, $time->month, $time->day, env("NONE_HOUR", "15"), env("NONE_MIN", "30"), 0); //set time to 18:00
        $none_13_30 = Carbon::create($time->year, $time->month, $time->day, env("NONE_M_HOUR", "13"), env("NONE_M_MIN", "30"), 0); //set time to 18:00
        $list_type_today_r_f = ["خش", "خم", "فش", "فم", "خپ", "فپ","خفش", "ففش", "خفم","ففم","خفپ","ففپ",
            "خشط", "خمط", "فشط", "فمط", "خپط", "فپط","خفشط", "ففشط", "خفمط","ففمط","خفپط","ففپط"
            ];
        $list_type_today_normal_cache = ["خ", "ف", "خن", "فن","خف","فف","ففن","خفن"];
        if (!$forbidden_day && $time->between($morning, $none_13_30, true) && in_array($type, $list_type_today_r_f))
            $array =  [
                "خش" => ["خش"],
                "فش" => ["فش"],
                "خم" => ["خم", "خپ"],
                "فم" => ["فم", "فپ"],
                "خپ" => ["خپ", "خم"],
                "فپ" => ["فپ", "فم"],
                "خفش" => ["خفش"],
                "ففش" => ["ففش"],
                "خفم" => ["خفم", "خفپ"],
                "ففم" => ["ففم", "ففپ"],
                "ففپ" => ["ففم", "ففپ"],
                "خفپ" => ["خفپ", "خفم",],
                "ففپط" => ["ففپط", "ففمط"],
                "خشط" => ["طخش"],
                "فشط" => ["فشط"],
                "خمط" => ["خمط", "خپط"],
                "فمط" => ["فمط", "فپط"],
                "خپط" => ["خپط", "خمط"],
                "فپط" => ["فپط", "فمط"],
                "خفشط" => ["خفشط"],
                "ففشط" => ["ففشط"],
                "خفمط" => ["خفمط", "خفپط"],
                "ففمط" => ["ففمط", "ففپط"],
                "خفپط" => ["خفپط", "خفمط",],
            ];
        elseif (!$forbidden_day && $time->between($morning, $none, true) && in_array($type, $list_type_today_normal_cache))
            $array = [
                "خ" => ["خ"],
                "ف" => ["ف"],
                "خن" => ["خن"],
                "فن" => ["فن"],
                "خف" => ["خف"],
                "فف" => ["فف"],
                "ففن" => ["ففن"],
                "خفن" => ["خفن"],

            ];
        else
            $array = [
                "خ" => ["خ", "خف"],
                "ف" => ["ف", "فف"],
                "خش" => ["خش", "خفش"],
                "فش" => ["فش", "ففش"],
                "خشط" => ["خشط", "خفشط"],
                "فشط" => ["فشط", "ففشط"],
                "خن" => ["خن", "خفن"],
                "فن" => ["فن", "ففن"],
                "خم" => ["خم", "خپ", "خفم", "خفپ"],
                "فم" => ["فم", "فپ", "ففم", "ففپ"],
                "خمط" => ["خمط", "خپط", "خفمط", "خفپط"],
                "فمط" => ["فمط", "فپط", "ففمط", "ففپط"],
                "خپ" => ["خپ", "خم", "خفم", "خفپ"],
                "فپ" => ["فپ", "فم", "ففم", "ففپ"],
                "خپط" => ["خپط", "خمط", "خفمط", "خفپط"],
                "فپط" => ["فپط", "فمط", "ففمط", "ففپط"],
                "خف" => ["خف", "خ"],
                "فف" => ["فف", "ف"],
                "خفش" => ["خفش", "خش"],
                "خفشط" => ["خفشط", "خشط"],
                "خفن" => ["خفن", "خن"],
                "ففش" => ["ففش", "فش"],
                "ففشط" => ["ففشط", "فشط"],
                "ففن" => ["ففن", "فن"],
                "خفم" => ["خفم", "خفپ", "خم", "خپ"],
                "ففم" => ["ففم", "ففپ", "فم", "فپ"],
                "خفپ" => ["خفپ", "خفم", "خپ", "خم"],
                "ففپ" => ["ففپ", "ففم", "فپ", "فم"],
                "خفمط" => ["خفمط", "خفپط", "خمط", "خپط"],
                "ففمط" => ["ففمط", "ففپط", "فمط", "فپط"],
                "خفپط" => ["خفپط", "خفمط", "خپط", "خمط"],
                "ففپط" => ["ففپط", "ففمط", "فپط", "فمط"],
            ];

        return data_get($array,$type);


    }
}


if (!function_exists('dataNow')) {
    function dataNow($time = null)
    {
        return \Carbon\Carbon::parse($time ?: now())
            ->timezone(config('app.timezone'))
            ->toDateTimeString();

    }
}

if (!function_exists('getBetweenMonth')) {
    function getBetweenMonth($month): array
    {
        if ($month == 12)
            $year = convertNumber(toJalali(now()->subYear(1), "Y"));
        else
            $year = convertNumber(toJalali(now(), "Y"));
        $days = (new Jalalian($year, $month, 15))->getMonthDays();
        $date_between[] = (new Jalalian($year, $month, 1))->toCarbon()->format("Y-m-d"); // [2016, 5, 7]
        $date_between[] = (new Jalalian($year, $month, $days))->toCarbon()->format("Y-m-d"); // [2016, 5, 7]
        return $date_between;
        switch ($month) {
            case 1;
                return ["2021-03-21", "2021-04-20"];
                break;
            case 2;
                return ["2021-04-21", "2021-05-21"];
                break;
            case 3;
                return ["2021-05-22", "2021-06-21"];
                break;
            case 4;
                return ["2021-06-22", "2021-07-22"];
                break;
            case 5;
                return ["2021-07-23", "2021-08-22"];
                break;
            case 6;
                return ["2021-08-23", "2021-09-22"];
                break;
            case 7;
                return ["2021-09-23", "2021-10-22"];
                break;
            case 8;
                return ["2021-10-23", "2021-11-21"];
                break;
            case 9;
                return ["2021-11-22", "2021-12-21"];
                break;
            case 10;
                return ["2021-12-21", "2022-01-20"];
                break;
            case 11;
                return ["2022-01-21", "2021-02-19"];
                break;
            case 12;
                return ["2022-02-20", "2022-03-20"];
                break;

        }
    }
}

if (!function_exists('getNameMonth')) {
    function getNameMonth($month): string
    {
        switch ($month) {
            case 1;
                return "فروردین";
                break;
            case 2;
                return "اردیبهشت";
                break;
            case 3;
                return "خرداد";
                break;
            case 4;
                return "تیر";
                break;
            case 5;
                return "مرداد";
                break;
            case 6;
                return "شهریور";
                break;
            case 7;
                return "مهر";
                break;
            case 8;
                return "آبان";
                break;
            case 9;
                return "آذر";
                break;
            case 10;
                return "دی";
                break;
            case 11;
                return "بهمن";
                break;
            case 12;
                return "اسفند";
                break;

        }
    }
}

if (!function_exists('getUnitNumber')) {
    function getUnitNumber($number)
    {
        $unit = "";
        $number = convertNumber($number);
        if ($number >= 99999999999)
            $unit = "تریلیون";
        elseif ($number >= 999999999)
            $unit = "میلیارد";
        elseif ($number >= 99999)
            $unit = "میلیون";
        elseif ($number >= 999)
            $unit = "هزار";
        return number_format($number, 0) . " " . $unit;

    }
}

if (!function_exists('getUnitPrice')) {
    function getUnitPrice($number)
    {
        $base = null;
        $number = convertNumber($number);
        if ($number >= 99999999999)
            $base = 1000000000000;
        elseif ($number >= 999999999)
            $base = 1000000000;
        elseif ($number >= 99999)
            $base = 1000000;
        elseif ($number >= 999)
            $base = 1000;
        return $base;

    }
}

if (!function_exists('number_format')) {
    function number_format($number, $decimal_precision = 0, $decimals_separator = '.', $thousands_separator = ',')
    {
        $number = explode('.', str_replace(' ', '', $number));
        $number[0] = str_split(strrev($number[0]), 3);
        $total_segments = count($number[0]);
        for ($i = 0; $i < $total_segments; $i++) {
            $number[0][$i] = strrev($number[0][$i]);
        }
        $number[0] = implode($thousands_separator, array_reverse($number[0]));
        if (!empty($number[1])) {
            $number[1] = round($number[1], $decimal_precision);
        }
        return implode($decimals_separator, $number);
    }
}

if (!function_exists('groupToWords')) {
    function groupToWords($group)
    {
        $digit1 = array(
            0 => 'صفر',
            1 => 'یک',
            2 => 'دو',
            3 => 'سه',
            4 => 'چهار',
            5 => 'پنج',
            6 => 'شش',
            7 => 'هفت',
            8 => 'هشت',
            9 => 'نه',
        );
        $digit1_5 = array(
            1 => 'یازده',
            2 => 'دوازده',
            3 => 'سیزده',
            4 => 'چهارده',
            5 => 'پانزده',
            6 => 'شانزده',
            7 => 'هفده',
            8 => 'هجده',
            9 => 'نوزده',
        );
        $digit2 = array(
            1 => 'ده',
            2 => 'بیست',
            3 => 'سی',
            4 => 'چهل',
            5 => 'پنجاه',
            6 => 'شصت',
            7 => 'هفتاد',
            8 => 'هشتاد',
            9 => 'نود'
        );
        $digit3 = array(
            1 => 'صد',
            2 => 'دویست',
            3 => 'سیصد',
            4 => 'چهارصد',
            5 => 'پانصد',
            6 => 'ششصد',
            7 => 'هفتصد',
            8 => 'هشتصد',
            9 => 'نهصد',
        );
        $d3 = floor($group / 100);
        $d2 = floor(($group - $d3 * 100) / 10);
        $d1 = $group - $d3 * 100 - $d2 * 10;

        $group_array = array();

        if ($d3 != 0) {
            $group_array[] = $digit3[$d3];
        }

        if ($d2 == 1 && $d1 != 0) { // 11-19
            $group_array[] = $digit1_5[$d1];
        } else if ($d2 != 0 && $d1 == 0) { // 10-20-...-90
            $group_array[] = $digit2[$d2];
        } else if ($d2 == 0 && $d1 == 0) { // 00
        } else if ($d2 == 0 && $d1 != 0) { // 1-9
            $group_array[] = $digit1[$d1];
        } else { // Others
            $group_array[] = $digit2[$d2];
            $group_array[] = $digit1[$d1];
        }

        if (!count($group_array)) {
            return FALSE;
        }

        return $group_array;
    }
}

if (!function_exists('numberToWords')) {
    function numberToWords($number)
    {
        $formated = number_format($number, 0, '.', ',');
        $groups = explode(',', $formated);
        $steps = array(
            1 => 'هزار',
            2 => 'میلیون',
            3 => 'بیلیون',
            4 => 'تریلیون',
            5 => 'کادریلیون',
            6 => 'کوینتریلیون',
            7 => 'سکستریلیون',
            8 => 'سپتریلیون',
            9 => 'اکتریلیون',
            10 => 'نونیلیون',
            11 => 'دسیلیون',
        );
        $steps = count($groups);

        $parts = array();
        foreach ($groups as $step => $group) {
            $t = array(
                'and' => 'و',
            );
            $group_words = groupToWords($group);
            if ($group_words) {
                $part = implode(' ' . $t['and'] . ' ', $group_words);
                if (isset($steps[$steps - $step - 1])) {
                    $part .= ' ' . $steps[$steps - $step - 1];
                }
                $parts[] = $part;
            }
        }
        return implode(' ' . $t['and'] . ' ', $parts);
    }
}

if (!function_exists('toJalaliByConvert')) {
    function toJalaliByConvert($time, $format = 'Y/m/d H:i:s')
    {
        $time = Carbon::parse($time)->format($format);
        return unConvertNumber(CalendarUtils::strftime($format, strtotime($time)));
    }
}

if (!function_exists('toJalali')) {
    function toJalali($time, $format = 'Y/m/d H:i:s')
    {
        return unConvertNumber(CalendarUtils::strftime($format, strtotime($time)));
    }
}

if (!function_exists('toAgo')) {
    function toAgo($time)
    {
        return Jalalian::forge($time)->ago();
    }
}

if (!function_exists('toGregorian')) {
    function toGregorian($time, $format = 'Y/m/d H:i:s')
    {
        $time = convertNumber($time);
        $year = 1399;
        $month = 01;
        $day = 1;
        $hour = 0;
        $minute = 0;
        $second = 0;
        $date_time = explode(" ", $time);

        if (!empty($date_time)) {
            $date = explode("/", $date_time[0]);
            $year = data_get($date, 0, 1399);
            $month = data_get($date, 1, 01);
            $day = data_get($date, 2, 01);
            if (!empty($date_time[1])) {
                $time = explode(":", $date_time[1]);
                $hour = data_get($time, 0, 0);
                $minute = data_get($time, 1, 0);
                $second = data_get($time, 2, 0);
            }
        }
        return (new Jalalian($year, $month, $day, $hour, $minute, $second))->toCarbon()->format($format);
    }
}

if (!function_exists('diffDate')) {
    function diffDate($time, $format = "%y year %m  month %d day", $by = 'now')
    {
        $date = Carbon::parse($time);
        if ($by)
            $date_diff = Carbon::parse($by);
        else
            $date_diff = Carbon::now();

        $diff = $date->diff($date_diff)->format($format);


        return $diff;
    }
}

if (!function_exists('persianTime')) {
    function persianTime($time)
    {
        $today = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $yesterday = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
        $tomorrow = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));
        $time_date = date("Y-m-d", strtotime($time));
        if ($today == $time_date) {
            return 'امروز ' . unConvertNumber(date("H:i", strtotime($time)));
        } elseif ($yesterday == $time_date) {
            return 'دیروز ' . unConvertNumber(date("H:i", strtotime($time)));
        } elseif ($tomorrow == $time_date) {
            return 'فردا ' . unConvertNumber(date("H:i", strtotime($time)));
        } else {
            $date = unConvertNumber(Jalalian::forge($time_date)->format('%y/%m/%d'));
            $date .= ' - ' . unConvertNumber(date("H:i", strtotime($time)));
            return $date;
        }
    }
}

if (!function_exists('arabicToPersian')) {
    function arabicToPersian($str)
    {
        $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩', 'ي', 'ك');
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', 'ی', 'ک');
        return str_replace($arabic, $persian, $str);
    }
}

if (!function_exists('convert2english')) {
    function convert2english($string)
    {
        $newNumbers = range(0, 9);
        // 1. Persian HTML decimal
        $persianDecimal = array('&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;');
        // 2. Arabic HTML decimal
        $arabicDecimal = array('&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;');
        // 3. Arabic Numeric
        $arabic = array('٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩');
        // 4. Persian Numeric
        $persian = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');

        $string = str_replace($persianDecimal, $newNumbers, $string);
        $string = str_replace($arabicDecimal, $newNumbers, $string);
        $string = str_replace($arabic, $newNumbers, $string);
        return str_replace($persian, $newNumbers, $string);
    }
}

if (!function_exists('convertNumber')) {
    function convertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($eastern, $western, $value);
    }
}

if (!function_exists('unConvertNumber')) {
    function unConvertNumber($value)
    {
        $western = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
        $eastern = ['۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '۰'];
        return str_replace($western, $eastern, $value);
    }
}

if (!function_exists('persianConvert')) {
    function persianConvert($string, $separator = '-')
    {
        $_transliteration = array(
            '/؆|؇|؈|؉|؊|؍|؎|ؐ|ؑ|ؒ|ؓ|ؔ|ؕ|ؖ|ؘ|ؙ|ؚ|؞|ٖ|ٗ|٘|ٙ|ٚ|ٛ|ٜ|ٝ|ٞ|ٟ|٪|٬|٭|ہ|ۂ|ۃ|۔|ۖ|ۗ|ۘ|ۙ|ۚ|ۛ|ۜ|۞|۟|۠|ۡ|ۢ|ۣ|ۤ|ۥ|ۦ|ۧ|ۨ|۩|۪|۫|۬|ۭ|ۯ|ﮧ|﮲|﮳|﮴|﮵|﮶|﮷|﮸|﮹|﮺|﮻|﮼|﮽|﮾|﮿|﯀|﯁|ﱞ|ﱟ|ﱠ|ﱡ|ﱢ|ﱣ|ﹰ|ﹱ|ﹲ|ﹳ|ﹴ|ﹶ|ﹷ|ﹸ|ٌ|ٍ|ﹸ|ﹹ|ْ|ﹺ|ﹻ|ﹼ|ً|ُ|ِ|َ|ّ|\]|\[|\}|\{|\||ٓ|ٰ|‌|ٔ|ء|ﹾ|ﹿ/' => '',
            '/أ|إ|ٱ|ٲ|ٳ|ٵ|ݳ|ݴ|ﭐ|ﭑ|ﺃ|ﺄ|ﺇ|ﺈ|ﺍ|ﺎ|𞺀|ﴼ|ﴽ|𞸀|إ|أ|آ/' => 'ا',
            '/ٮ|ݕ|ݖ|ﭒ|ﭓ|ﭔ|ﭕ|ﺏ|ﺐ|ﺑ|ﺒ|𞸁|𞸜|𞸡|𞹡|𞹼|𞺁|𞺡/' => 'ب',
            '/ڀ|ݐ|ݔ|ﭖ|ﭗ|ﭘ|ﭙ|ﭚ|ﭛ|ﭜ|ﭝ/' => 'پ',
            '/ٹ|ٺ|ٻ|ټ|ݓ|ﭞ|ﭟ|ﭠ|ﭡ|ﭢ|ﭣ|ﭤ|ﭥ|ﭦ|ﭧ|ﭨ|ﭩ|ﺕ|ﺖ|ﺗ|ﺘ|𞸕|𞸵|𞹵|𞺕|𞺵/' => 'ت',
            '/ٽ|ٿ|ݑ|ﺙ|ﺚ|ﺛ|ﺜ|𞸖|𞸶|𞹶|𞺖|𞺶/' => 'ث',
            '/ڃ|ڄ|ﭲ|ﭳ|ﭴ|ﭵ|ﭶ|ﭷ|ﭸ|ﭹ|ﺝ|ﺞ|ﺟ|ﺠ|𞸂|𞸢|𞹂|𞹢|𞺂|𞺢/' => 'ج',
            '/ڇ|ڿ|ݘ|ﭺ|ﭻ|ﭼ|ﭽ|ﭾ|ﭿ|ﮀ|ﮁ|𞸃|𞺃/' => 'چ',
            '/ځ|ݮ|ݯ|ݲ|ݼ|ﺡ|ﺢ|ﺣ|ﺤ|𞸇|𞸧|𞹇|𞹧|𞺇|𞺧/' => 'ح',
            '/ڂ|څ|ݗ|ﺥ|ﺦ|ﺧ|ﺨ|𞸗|𞸷|𞹗|𞹷|𞺗|𞺷/' => 'خ',
            '/ڈ|ډ|ڊ|ڌ|ڍ|ڎ|ڏ|ڐ|ݙ|ݚ|ﺩ|ﺪ|𞺣|ﮂ|ﮃ|ﮈ|ﮉ/' => 'د',
            '/ﱛ|ﱝ|ﺫ|ﺬ|𞸘|𞺘|𞺸|ﮄ|ﮅ|ﮆ|ﮇ|ۮ/' => 'ذ',
            '/٫|ڑ|ڒ|ړ|ڔ|ڕ|ږ|ݛ|ݬ|ﮌ|ﮍ|ﱜ|ﺭ|ﺮ|𞸓|𞺓|𞺳/' => 'ر',
            '/ڗ|ڙ|ݫ|ݱ|ﺯ|ﺰ|𞸆|𞺆|𞺦/' => 'ز',
            '/ﮊ|ﮋ|ژ|ۯ/' => 'ژ',
            '/ښ|ݽ|ݾ|ﺱ|ﺲ|ﺳ|ﺴ|𞸎|𞸮|𞹎|𞹮|𞺎|𞺮/' => 'س',
            '/ڛ|ۺ|ݜ|ݭ|ݰ|ﺵ|ﺶ|ﺷ|ﺸ|𞸔|𞸴|𞹔|𞹴|𞺔|𞺴/' => 'ش',
            '/ڝ|ﺹ|ﺺ|ﺻ|ﺼ|𞸑|𞹑|𞸱|𞹱|𞺑|𞺱/' => 'ص',
            '/ڞ|ۻ|ﺽ|ﺾ|ﺿ|ﻀ|𞸙|𞸹|𞹙|𞹹|𞺙|𞺹/' => 'ض',
            '/ﻁ|ﻂ|ﻃ|ﻄ|𞸈|𞹨|𞺈|𞺨/' => 'ط',
            '/ڟ|ﻅ|ﻆ|ﻇ|ﻈ|𞸚|𞹺|𞺚|𞺺/' => 'ظ',
            '/؏|ڠ|ﻉ|ﻊ|ﻋ|ﻌ|𞸏|𞸯|𞹏|𞹯|𞺏|𞺯/' => 'ع',
            '/ۼ|ݝ|ݞ|ݟ|ﻍ|ﻎ|ﻏ|ﻐ|𞸛|𞸻|𞹛|𞹻|𞺛|𞺻/' => 'غ',
            '/؋|ڡ|ڢ|ڣ|ڤ|ڥ|ڦ|ݠ|ݡ|ﭪ|ﭫ|ﭬ|ﭭ|ﭮ|ﭯ|ﭰ|ﭱ|ﻑ|ﻒ|ﻓ|ﻔ|𞸐|𞸞|𞸰|𞹰|𞹾|𞺐|𞺰/' => 'ف',
            '/ٯ|ڧ|ڨ|ﻕ|ﻖ|ﻗ|ﻘ|𞸒|𞸟|𞸲|𞹒|𞹟|𞹲|𞺒|𞺲|؈/' => 'ق',
            '/ػ|ؼ|ك|ڪ|ګ|ڬ|ڭ|ڮ|ݢ|ݣ|ݤ|ݿ|ﮎ|ﮏ|ﮐ|ﮑ|ﯓ|ﯔ|ﯕ|ﯖ|ﻙ|ﻚ|ﻛ|ﻜ|𞸊|𞸪|𞹪/' => 'ک',
            '/ڰ|ڱ|ڲ|ڳ|ڴ|ﮒ|ﮓ|ﮔ|ﮕ|ﮖ|ﮗ|ﮘ|ﮙ|ﮚ|ﮛ|ﮜ|ﮝ/' => 'گ',
            '/ڵ|ڶ|ڷ|ڸ|ݪ|ﻝ|ﻞ|ﻟ|ﻠ|𞸋|𞸫|𞹋|𞺋|𞺫/' => 'ل',
            '/۾|ݥ|ݦ|ﻡ|ﻢ|ﻣ|ﻤ|𞸌|𞸬|𞹬|𞺌|𞺬/' => 'م',
            '/ڹ|ں|ڻ|ڼ|ڽ|ݧ|ݨ|ݩ|ﮞ|ﮟ|ﮠ|ﮡ|ﻥ|ﻦ|ﻧ|ﻨ|𞸍|𞸝|𞸭|𞹍|𞹝|𞹭|𞺍|𞺭/' => 'ن',
            '/ؤ|ٶ|ٷ|ۄ|ۅ|ۆ|ۇ|ۈ|ۉ|ۊ|ۋ|ۏ|ݸ|ݹ|ﯗ|ﯘ|ﯙ|ﯚ|ﯛ|ﯜ|ﯝ|ﯞ|ﯟ|ﯠ|ﯡ|ﯢ|ﯣ|ﺅ|ﺆ|ﻭ|ﻮ|𞸅|𞺅|𞺥/' => 'و',
            '/ة|ھ|ۀ|ە|ۿ|ﮤ|ﮥ|ﮦ|ﮩ|ﮨ|ﮪ|ﮫ|ﮬ|ﮭ|ﺓ|ﺔ|ﻩ|ﻪ|ﻫ|ﻬ|𞸤|𞹤|𞺄|ة/' => 'ه',
            '/ؠ|ئ|ؽ|ؾ|ؿ|ى|ي|ٸ|ۍ|ێ|ې|ۑ|ے|ۓ|ݵ|ݶ|ݷ|ݺ|ݻ|ﮢ|ﮣ|ﮮ|ﮯ|ﮰ|ﮱ|ﯤ|ﯥ|ﯦ|ﯧ|ﯨ|ﯩ|ﯼ|ﯽ|ﯾ|ﯿ|ﺉ|ﺊ|ﺋ|ﺌ|ﻯ|ﻰ|ﻱ|ﻲ|ﻳ|ﻴ|𞸉|𞸩|𞹉|𞹩|𞺉|𞺩/' => 'ی',
            '/ٴ|۽|ﺀ/' => 'ء',
            '/ﻵ|ﻶ|ﻷ|ﻸ|ﻹ|ﻺ|ﻻ|ﻼ/' => 'لا',
            '/\؟/' => '',
            '/ﷲ/' => 'الله',
            '/﷼/' => 'ریال',
            '/ﷳ/' => 'اکبر',
            '/ﷴ/' => 'محمد',
            '/ﷵ/' => 'صلعم',
            '/ﷶ/' => 'رسول',
            '/ﷷ/' => 'علیه',
            '/ﷸ/' => 'وسلم',
            '/ﷹ/' => 'صلی',
            '/ﷺ/' => 'صلی الله علیه وسلم',
            '/ﷻ/' => 'جل جلاله'
        );

        $quotedReplacement = preg_quote($separator, '/');
        $merge = array(
            '/[^\s\p{Zs}\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
            '/[\s\p{Zs}]+/mu' => $separator,
            sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
        );
        $map = $_transliteration + $merge;
        unset($_transliteration);
        return strtolower(preg_replace(array_keys($map), array_values($map), $string));
    }
}

if (!function_exists('slug_seo')) {
    function slug_seo($string, $separator = '-')
    {
        $_transliteration = array(
            '/ä|æ|ǽ/' => 'ae',
            '/ö|œ/' => 'oe',
            '/ü/' => 'ue',
            '/Ä/' => 'Ae',
            '/Ü/' => 'Ue',
            '/Ö/' => 'Oe',
            '/À|Á|Â|Ã|Å|Ǻ|Ā|Ă|Ą|Ǎ/' => 'A',
            '/à|á|â|ã|å|ǻ|ā|ă|ą|ǎ|ª/' => 'a',
            '/Ç|Ć|Ĉ|Ċ|Č/' => 'C',
            '/ç|ć|ĉ|ċ|č/' => 'c',
            '/Ð|Ď|Đ/' => 'D',
            '/ð|ď|đ/' => 'd',
            '/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě/' => 'E',
            '/è|é|ê|ë|ē|ĕ|ė|ę|ě/' => 'e',
            '/Ĝ|Ğ|Ġ|Ģ/' => 'G',
            '/ĝ|ğ|ġ|ģ/' => 'g',
            '/Ĥ|Ħ/' => 'H',
            '/ĥ|ħ/' => 'h',
            '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ/' => 'I',
            '/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı/' => 'i',
            '/Ĵ/' => 'J',
            '/ĵ/' => 'j',
            '/Ķ/' => 'K',
            '/ķ/' => 'k',
            '/Ĺ|Ļ|Ľ|Ŀ|Ł/' => 'L',
            '/ĺ|ļ|ľ|ŀ|ł/' => 'l',
            '/Ñ|Ń|Ņ|Ň/' => 'N',
            '/ñ|ń|ņ|ň|ŉ/' => 'n',
            '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ/' => 'O',
            '/ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º/' => 'o',
            '/Ŕ|Ŗ|Ř/' => 'R',
            '/ŕ|ŗ|ř/' => 'r',
            '/Ś|Ŝ|Ş|Ș|Š/' => 'S',
            '/ś|ŝ|ş|ș|š|ſ/' => 's',
            '/Ţ|Ț|Ť|Ŧ/' => 'T',
            '/ţ|ț|ť|ŧ/' => 't',
            '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ/' => 'U',
            '/ù|ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ/' => 'u',
            '/Ý|Ÿ|Ŷ/' => 'Y',
            '/ý|ÿ|ŷ/' => 'y',
            '/Ŵ/' => 'W',
            '/ŵ/' => 'w',
            '/Ź|Ż|Ž/' => 'Z',
            '/ź|ż|ž/' => 'z',
            '/Æ|Ǽ/' => 'AE',
            '/ß/' => 'ss',
            '/Ĳ/' => 'IJ',
            '/ĳ/' => 'ij',
            '/Œ/' => 'OE',
            '/ƒ/' => 'f',
            '/\_/' => '-',
            '/\?|\!|\@|\#|\$|\%|\^|\&|\*|\(|\)/' => '',
            '/\؟/' => ''
        );

        $quotedReplacement = preg_quote($separator, '/');
        $merge = array(
            '/[^\s\p{Zs}\p{Ll}\p{Lm}\p{Lo}\p{Lt}\p{Lu}\p{Nd}]/mu' => ' ',
            '/[\s\p{Zs}]+/mu' => $separator,
            sprintf('/^[%s]+|[%s]+$/', $quotedReplacement, $quotedReplacement) => '',
        );
        $map = $_transliteration + $merge;
        unset($_transliteration);
        return strtolower(preg_replace(array_keys($map), array_values($map), $string));
    }
}

if (!function_exists('fagd')) {

    function fagd($str, $z = "", $method = 'normal')
    {
//        $output ="";
//        $e_output = "";
//        $num = "";
        $p_chars = array(
            'آ' => array('ﺂ', 'ﺂ', 'آ'),
            'ا' => array('ﺎ', 'ﺎ', 'ا'),
            'ب' => array('ﺐ', 'ﺒ', 'ﺑ'),
            'پ' => array('ﭗ', 'ﭙ', 'ﭘ'),
            'ت' => array('ﺖ', 'ﺘ', 'ﺗ'),
            'ث' => array('ﺚ', 'ﺜ', 'ﺛ'),
            'ج' => array('ﺞ', 'ﺠ', 'ﺟ'),
            'چ' => array('ﭻ', 'ﭽ', 'ﭼ'),
            'ح' => array('ﺢ', 'ﺤ', 'ﺣ'),
            'خ' => array('ﺦ', 'ﺨ', 'ﺧ'),
            'د' => array('ﺪ', 'ﺪ', 'ﺩ'),
            'ذ' => array('ﺬ', 'ﺬ', 'ﺫ'),
            'ر' => array('ﺮ', 'ﺮ', 'ﺭ'),
            'ز' => array('ﺰ', 'ﺰ', 'ﺯ'),
            'ژ' => array('ﮋ', 'ﮋ', 'ﮊ'),
            'س' => array('ﺲ', 'ﺴ', 'ﺳ'),
            'ش' => array('ﺶ', 'ﺸ', 'ﺷ'),
            'ص' => array('ﺺ', 'ﺼ', 'ﺻ'),
            'ض' => array('ﺾ', 'ﻀ', 'ﺿ'),
            'ط' => array('ﻂ', 'ﻄ', 'ﻃ'),
            'ظ' => array('ﻆ', 'ﻈ', 'ﻇ'),
            'ع' => array('ﻊ', 'ﻌ', 'ﻋ'),
            'غ' => array('ﻎ', 'ﻐ', 'ﻏ'),
            'ف' => array('ﻒ', 'ﻔ', 'ﻓ'),
            'ق' => array('ﻖ', 'ﻘ', 'ﻗ'),
            'ک' => array('ﻚ', 'ﻜ', 'ﻛ'),
            'گ' => array('ﮓ', 'ﮕ', 'ﮔ'),
            'ل' => array('ﻞ', 'ﻠ', 'ﻟ'),
            'م' => array('ﻢ', 'ﻤ', 'ﻣ'),
            'ن' => array('ﻦ', 'ﻨ', 'ﻧ'),
            'و' => array('ﻮ', 'ﻮ', 'ﻭ'),
            'ی' => array('ﯽ', 'ﯿ', 'ﯾ'),
            'ك' => array('ﻚ', 'ﻜ', 'ﻛ'),
            'ي' => array('ﻲ', 'ﻴ', 'ﻳ'),
            'أ' => array('ﺄ', 'ﺄ', 'ﺃ'),
            'ؤ' => array('ﺆ', 'ﺆ', 'ﺅ'),
            'إ' => array('ﺈ', 'ﺈ', 'ﺇ'),
            'ئ' => array('ﺊ', 'ﺌ', 'ﺋ'),
            'ة' => array('ﺔ', 'ﺘ', 'ﺗ')
        );
        $nastaligh = array(
            'ه' => array('ﮫ', 'ﮭ', 'ﮬ', 'ه')
        );
        $normal = array(
            'ه' => array('ﻪ', 'ﻬ', 'ﻫ')
        );
        $mp_chars = array('آ', 'ا', 'د', 'ذ', 'ر', 'ز', 'ژ', 'و', 'أ', 'إ', 'ؤ');
        $ignorelist = array('', 'ٌ', 'ٍ', 'ً', 'ُ', 'ِ', 'َ', 'ّ', 'ٓ', 'ٰ', 'ٔ', 'ﹶ', 'ﹺ', 'ﹸ', 'ﹼ', 'ﹾ', 'ﹴ', 'ﹰ', 'ﱞ', 'ﱟ', 'ﱠ', 'ﱡ', 'ﱢ', 'ﱣ',);
        if ($method == 'nastaligh') {
            $p_chars = array_merge($p_chars, $nastaligh);
        } else {
            $p_chars = array_merge($p_chars, $normal);
        }
        $str_len = utf8StrLen($str);
        preg_match_all("/./u", $str, $ar);
        for ($i = 0; $i < $str_len; $i++) {
            $str1 = $ar[0][$i];
            if (in_array($ar[0][$i + 1], $ignorelist)) {
                $str_next = $ar[0][$i + 2];
                if ($i == 2) $str_back = $ar[0][$i - 2];
                if ($i != 2) $str_back = $ar[0][$i - 1];
            } elseif (!in_array($ar[0][$i - 1], $ignorelist)) {
                $str_next = $ar[0][$i + 1];
                if ($i != 0) $str_back = $ar[0][$i - 1];

            } else {
                if (isset($ar[0][$i + 1]) && !empty($ar[0][$i + 1])) {
                    $str_next = $ar[0][$i + 1];
                } else {
                    $str_next = $ar[0][$i - 1];
                }
                if ($i != 0) $str_back = $ar[0][$i - 2];
            }
            if (!in_array($str1, $ignorelist)) {
                if (array_key_exists($str1, $p_chars)) {
                    if (!$str_back or $str_back == " " or !array_key_exists($str_back, $p_chars)) {
                        if (!array_key_exists($str_back, $p_chars) and !array_key_exists($str_next, $p_chars)) $output = $str1 . $output;
                        else $output = $p_chars[$str1][2] . $output;
                        continue;
                    } elseif (array_key_exists($str_next, $p_chars) and array_key_exists($str_back, $p_chars)) {
                        if (in_array($str_back, $mp_chars) and array_key_exists($str_next, $p_chars)) {
                            $output = $p_chars[$str1][2] . $output;
                        } else {
                            $output = $p_chars[$str1][1] . $output;
                        }
                        continue;
                    } elseif (array_key_exists($str_back, $p_chars) and !array_key_exists($str_next, $p_chars)) {
                        if (in_array($str_back, $mp_chars)) {
                            $output = $str1 . $output;
                        } else {
                            $output = $p_chars[$str1][0] . $output;
                        }
                        continue;
                    }

                } elseif ($z == "fa") {

                    $number = array("٠", "١", "٢", "٣", "٤", "٥", "٦", "٧", "٨", "٩", "۴", "۵", "۶", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
                    switch ($str1) {
                        case ")" :
                            $str1 = "(";
                            break;
                        case "(" :
                            $str1 = ")";
                            break;
                        case "}" :
                            $str1 = "{";
                            break;
                        case "{" :
                            $str1 = "}";
                            break;
                        case "]" :
                            $str1 = "[";
                            break;
                        case "[" :
                            $str1 = "]";
                            break;
                        case ">" :
                            $str1 = "<";
                            break;
                        case "<" :
                            $str1 = ">";
                            break;
                    }
                    if (in_array($str1, $number)) {
                        $num .= $str1;
                        $str1 = "";
                    }
                    if (!in_array($str_next, $number)) {
                        $str1 .= $num;
                        $num = "";
                    }
                    $output = $str1 . $output;
                } else {
                    if (($str1 == "،") or ($str1 == "؟") or ($str1 == "ء") or (array_key_exists($str_next, $p_chars) and array_key_exists($str_back, $p_chars)) or
                        ($str1 == " " and array_key_exists($str_back, $p_chars)) or ($str1 == " " and array_key_exists($str_next, $p_chars))) {
                        if ($e_output) {
                            $output = $e_output . $output;
                            $e_output = "";
                        }
                        $output = $str1 . $output;
                    } else {
                        $e_output .= $str1;
                        if (array_key_exists($str_next, $p_chars) or $str_next == "") {
                            $output = $e_output . $output;
                            $e_output = "";
                        }
                    }
                }
            } else {
                $output = $str1 . $output;
            }
            $str_next = null;
            $str_back = null;
        }
        return $output;
    }

    if (!function_exists('createPassword')) {
        function createPassword()
        {
            $digits = array_flip(range('2', '9'));
            $lowercase = array_flip(range('a', 'z'));
            $lowercase = array_diff($lowercase, ['i', "l", "o"]);
            $uppercase = array_flip(range('A', 'Z'));
            $uppercase = array_diff($uppercase, ["I", "L", "O"]);
            $special = array_flip(str_split('@#$'));
            $combined = array_merge($digits, $lowercase, $uppercase, $special);

            $password = str_shuffle(array_rand($digits) .
                array_rand($lowercase) .
                array_rand($uppercase) .
                array_rand($special) .
                implode(array_rand($combined, rand(4, 8))));

            return $password;

        }
    }


}

