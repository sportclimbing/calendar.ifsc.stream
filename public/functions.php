<?php

function error_500()
{
    header(' ', true, 500);
    exit;
}

function http_get($url)
{
    $opts = stream_context_create([
        'http' => [
            'method' => "GET",
            'header' =>
                "Accept-language: en\r\n" .
                "Accept: */*\r\n" .
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0\r\n"
        ]
    ]);

    return @file_get_contents($url, false, $opts);
}