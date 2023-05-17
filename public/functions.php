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

function download_url($json, $format)
{
    return isset($json->assets[$format]->browser_download_url)
        ? $json->assets[$format]->browser_download_url
        : null;
}
