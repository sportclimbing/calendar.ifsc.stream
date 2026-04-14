<?php

function error_503()
{
    header(' ', true, 503);
    echo "Service temporarily unavailable", PHP_EOL;
    exit;
}

function http_get($url)
{
    return @file_get_contents($url, false, stream_context_create([
        'http' => [
            'method' => "GET",
            'header' =>
                "Accept-language: en\r\n" .
                "Accept: */*\r\n" .
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0\r\n"
        ]
    ]));
}

function track_calendar_download($format)
{
    $measurement_id = getenv('GA_MEASUREMENT_ID');
    $api_secret = getenv('GA_API_SECRET');

    if (!$measurement_id || !$api_secret) {
        return;
    }

    $hash = 'c9aa6d60750316045aa338e761286b65';
    $client_id = hash('sha256', (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . $hash);

    $data = json_encode([
        'client_id' => $client_id,
        'events' => [[
            'name' => 'calendar_download',
            'params' => [
                'format' => $format,
            ],
        ]],
    ]);

    @file_get_contents(
        "https://www.google-analytics.com/mp/collect?measurement_id={$measurement_id}&api_secret={$api_secret}",
        false,
        stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $data,
                'ignore_errors' => true,
            ],
        ])
    );
}
