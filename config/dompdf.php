<?php

return [

    'show_warnings' => false,
    'orientation' => 'portrait',

    'defines' => [
        'font_dir' => storage_path('fonts/'), // مسیر فونت‌ها
        'font_cache' => storage_path('fonts/'), // مسیر کش فونت‌ها

        'default_font' => 'Vazirmatn', // نام فونت پیش‌فرض

        'font_data' => [
            'Vazir' => [
                'R'  => 'Vazirmatn-Regular.ttf', // فایل فونت معمولی
                'B'  => 'Vazirmatn-Bold.ttf', // فایل فونت بولد
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
        ],
    ],
];
