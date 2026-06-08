<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Adapter;

use SportClimbing\Port\HttpClient;
use SportClimbing\Port\HttpException;

final readonly class FileGetContentsHttpClient implements HttpClient
{
    public function get(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' =>
                    "Accept-language: en\r\n" .
                    "Accept: */*\r\n" .
                    "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:109.0) Gecko/20100101 Firefox/111.0\r\n",
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        if ($result === false) {
            throw new HttpException("Failed to fetch URL: {$url}");
        }

        return $result;
    }
}
