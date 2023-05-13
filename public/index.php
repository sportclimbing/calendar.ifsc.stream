<?php

error_reporting(-1);
ini_set('display_errors', 0);

define('LATEST_RELEASE_URL',  'https://api.github.com/repos/sportclimbing/ifsc-calendar/releases/latest');
define('CALENDAR_FILE', 'calendar.ics');
define('CACHE_SECONDS', 60 * 1);

$fileExists = is_file(CALENDAR_FILE);

function error_500()
{
    header(' ', true, 500);
    exit;
}

if ($fileExists) {
    $timeDiff = time() - filemtime(CALENDAR_FILE);
} else {
    $timeDiff = CACHE_SECONDS;
}

if (!$fileExists || $timeDiff > CACHE_SECONDS) {
    $opts = stream_context_create([
        'http' => [
            'method' => "GET",
            'header' =>
                "Accept-language: en\r\n" .
                "Accept: */*\r\n" .
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0\r\n"
        ]
    ]);

    $contents = @file_get_contents(LATEST_RELEASE_URL, false, $opts);

    if (!$contents) {
        error_500();
    }

    $json = @json_decode($contents);

    if (json_last_error() || !isset($json->assets[0]->browser_download_url)) {
        error_500();
    }

    $timeDiff = 600;
    $contents = @file_get_contents($json->assets[0]->browser_download_url);

    if (!$contents) {
        error_500();
    }

    file_put_contents(CALENDAR_FILE, $contents, LOCK_EX);
    touch(CALENDAR_FILE);
} else {
    $timeDiff = CACHE_SECONDS - $timeDiff;
}

header("Cache-Control: max-age={$timeDiff}");
header('Content-Disposition: attachment; filename="ifsc-calendar.ics"');
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Length: ' . filesize(CALENDAR_FILE));

readfile(CALENDAR_FILE);
