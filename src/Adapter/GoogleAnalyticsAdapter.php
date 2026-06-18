<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Adapter;

use SportClimbing\IcsGenerator\FilterParams;
use SportClimbing\Port\AnalyticsClient;

final readonly class GoogleAnalyticsAdapter implements AnalyticsClient
{
    private const string GA_ENDPOINT = 'https://www.google-analytics.com/mp/collect';
    private const string CLIENT_ID_SALT = 'c9aa6d60750316045aa338e761286b65';

    public function __construct(
        private string $measurementId,
        private string $apiSecret,
    ) {
    }

    public function trackDownload(string $format, ?FilterParams $filterParams): void
    {
        $clientId = hash(
            'sha256',
            ($_SERVER['REMOTE_ADDR'] ?? '')
            . ($_SERVER['HTTP_USER_AGENT'] ?? '')
            . self::CLIENT_ID_SALT,
        );

        $params = ['format' => $format];

        if ($filterParams && !$filterParams->isEmpty()) {
            if ($filterParams->disciplines !== []) {
                $params['discipline'] = implode(',', $filterParams->disciplines);
            }
            if ($filterParams->kinds !== []) {
                $params['kind'] = implode(',', $filterParams->kinds);
            }
            if ($filterParams->categories !== []) {
                $params['category'] = implode(',', $filterParams->categories);
            }
        }

        $payload = json_encode([
            'client_id' => $clientId,
            'events' => [[
                'name' => 'calendar_download_v2',
                'params' => $params,
            ]],
        ]);

        $url = self::GA_ENDPOINT . "?measurement_id={$this->measurementId}&api_secret={$this->apiSecret}";

        @file_get_contents($url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => $payload,
                'ignore_errors' => true,
            ],
        ]));
    }
}
