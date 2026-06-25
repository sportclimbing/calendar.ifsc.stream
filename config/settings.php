<?php

declare(strict_types=1);

return [

    'cache' => [
        'dir' => dirname(__DIR__) . '/var/cache',
    ],

    'calendar' => [
        'base_url' => 'https://github.com/sportclimbing/ifsc-calendar/releases/latest/download',
        'json_filename' => 'IFSC-World-Cups-and-World-Championships.json',
        'prod_id' => '-//ifsc/ical//2.0/EN',
        'duration' => 'PT1H',
        'cal_name' => 'IFSC',
        'show_config_notice' => true,
    ],

    'validation' => [
        'discipline' => ['boulder', 'lead', 'speed'],
        'kind' => ['qualification', 'semi-final', 'final'],
        'category' => ['men', 'women'],
        'series' => ['world', 'para'],
    ],

    'analytics' => [
        'measurement_id' => getenv('GA_MEASUREMENT_ID') ?: '',
        'api_secret' => getenv('GA_API_SECRET') ?: '',
    ],

];
