<?php

error_reporting(-1);
ini_set('display_errors', 0);

require __DIR__ . '/functions.php';

define('LATEST_RELEASE_URL',  'https://api.github.com/repos/sportclimbing/ifsc-calendar/releases/latest');
define('CACHE_SECONDS', 60 * 1);

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

define('CALENDAR_FILE', $format === 'ics'
    ? CALENDAR_FILE_ICS
    : CALENDAR_FILE_JSON
);

$fileExists = is_file(CALENDAR_FILE);

if ($fileExists) {
    $timeDiff = time() - filemtime(CALENDAR_FILE);
} else {
    $timeDiff = CACHE_SECONDS;
}

if ($noCache || !$fileExists || $timeDiff > CACHE_SECONDS) {
    $contents = http_get(LATEST_RELEASE_URL);

    if (!$contents) {
        error_503();
    }

    $json = @json_decode($contents);

    if (json_last_error() || !isset($json->assets[$formats[$format]]->browser_download_url)) {
        error_503();
    }

    $contents = http_get($json->assets[$formats[$format]]->browser_download_url);

    if (!$contents) {
        error_503();
    }

    file_put_contents(CALENDAR_FILE, $contents, LOCK_EX);
    touch(CALENDAR_FILE);

    $timeDiff = 600;
} else {
    $timeDiff = CACHE_SECONDS - $timeDiff;
}

header("Cache-Control: max-age={$timeDiff}");
header("Content-Disposition: attachment; filename=\"ifsc-calendar.{$format}\"");
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Length: ' . filesize(CALENDAR_FILE));

readfile(CALENDAR_FILE);
