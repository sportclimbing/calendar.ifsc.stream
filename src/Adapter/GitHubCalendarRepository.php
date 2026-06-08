<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Adapter;

use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\CalendarUnavailableException;
use SportClimbing\Port\HttpClient;

final readonly class GitHubCalendarRepository implements CalendarRepository
{
    public function __construct(
        private HttpClient $httpClient,
        private string $icsUrl,
        private string $jsonUrl,
    ) {
    }

    public function getIcs(): string
    {
        return $this->httpClient->get($this->icsUrl);
    }

    public function getEvents(): array
    {
        $json = $this->httpClient->get($this->jsonUrl);
        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['events'])) {
            throw new CalendarUnavailableException('Invalid JSON structure from calendar source');
        }

        return $data;
    }
}
