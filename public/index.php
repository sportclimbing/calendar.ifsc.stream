<?php

error_reporting(-1);
ini_set('display_errors', 0);

require __DIR__ . '/functions.php';

define('CACHE_SECONDS', 60);

define('CALENDAR_FILE_ICS', 'cache/calendar.ics');
define('CALENDAR_FILE_JSON', 'cache/calendar.json');

$format = isset($_GET['format']) ? (string) $_GET['format'] : '';
$noCache = isset($_GET['nocache']);

$formats = [
    'ics' => 'https://github.com/sportclimbing/ifsc-calendar/releases/latest/download/IFSC-World-Cups-and-World-Championships.ics',
    'json' => 'https://github.com/sportclimbing/ifsc-calendar/releases/latest/download/IFSC-World-Cups-and-World-Championships.json',
];

if (!isset($formats[$format])) {
    $format = 'ics';
}

if ($format === 'ics') {
    $calendarFile = CALENDAR_FILE_ICS;
    $contentType = 'text/calendar';
} else {
    $calendarFile = CALENDAR_FILE_JSON;
    $contentType = 'application/json';
}

$fileExists = is_file($calendarFile);

if ($fileExists) {
    $timeDiff = time() - filemtime($calendarFile);
} else {
    $timeDiff = CACHE_SECONDS;
}

if ($noCache || !$fileExists || $timeDiff > CACHE_SECONDS) {
    $contents = http_get($formats[$format]);

    if (!$contents || @file_put_contents($calendarFile, $contents, LOCK_EX) === false) {
        error_503();
    }

    touch($calendarFile);
    $contentLength = strlen($contents);
} else {
    $contentLength = filesize($calendarFile);
}

$maxAge = abs(CACHE_SECONDS - $timeDiff);

header("Cache-Control: max-age={$maxAge}");
header("Content-Disposition: attachment; filename=\"ifsc-calendar.{$format}\"");
header("Content-Type: {$contentType}; charset=utf-8");
header("Content-Length: {$contentLength}");

readfile($calendarFile);
