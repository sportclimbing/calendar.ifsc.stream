<?php

error_reporting(-1);
ini_set('display_errors', 0);

require __DIR__ . '/functions.php';

define('LATEST_RELEASE_URL',  'https://api.github.com/repos/sportclimbing/ifsc-calendar/releases/latest');
define('CACHE_SECONDS', 60);

define('CALENDAR_FILE_ICS', 'cache/calendar.ics');
define('CALENDAR_FILE_JSON', 'cache/calendar.json');

$format = isset($_GET['format']) ? (string) $_GET['format'] : '';
$noCache = isset($_GET['nocache']);

$formats = [
    'ics' => 0,
    'json' => 1,
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
    $contents = http_get(LATEST_RELEASE_URL);

    if (!$contents) {
        error_503();
    }

    $json = @json_decode($contents);
    $downloadUrl = download_url($json, $formats[$format]);

    if (json_last_error() || !isset($downloadUrl)) {
        error_503();
    }

    $contents = http_get($downloadUrl);

    if (!$contents) {
        error_503();
    }

    file_put_contents($calendarFile, $contents, LOCK_EX);
    touch($calendarFile);

    $timeDiff = 600;
    $contentLength = strlen($contents);
} else {
    $timeDiff = CACHE_SECONDS - $timeDiff;
    $contentLength = filesize($calendarFile);
}

header("Cache-Control: max-age={$timeDiff}");
header("Content-Disposition: attachment; filename=\"ifsc-calendar.{$format}\"");
header("Content-Type: {$contentType}; charset=utf-8");
header('Content-Length: ' . $contentLength);

readfile($calendarFile);
