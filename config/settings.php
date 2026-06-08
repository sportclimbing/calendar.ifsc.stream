<?php

declare(strict_types=1);

return [

    'cache' => [
        'dir' => dirname(__DIR__) . '/var/cache',
        'seconds' => 60,
    ],

    'calendar' => [
        'base_url' => 'https://github.com/sportclimbing/ifsc-calendar/releases/latest/download',
        'ics_filename' => 'IFSC-World-Cups-and-World-Championships.ics',
        'json_filename' => 'IFSC-World-Cups-and-World-Championships.json',
        'prod_id' => '-//IFSC//IFSC Calendar//EN',
        'duration' => 'PT12H',
        'cal_name' => 'IFSC World Cups and World Championships',
    ],

    'analytics' => [
        'measurement_id' => getenv('GA_MEASUREMENT_ID') ?: '',
        'api_secret' => getenv('GA_API_SECRET') ?: '',
    ],

];
