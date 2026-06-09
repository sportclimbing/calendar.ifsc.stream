<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Adapter;

use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\CalendarUnavailableException;

final readonly class LocalCalendarRepository implements CalendarRepository
{
    public function __construct(
        private string $cacheFile,
    ) {
    }

    public function getEvents(): array
    {
        if (!is_file($this->cacheFile)) {
            throw new CalendarUnavailableException(
                'Calendar data not yet available — please try again shortly.',
            );
        }

        $json = file_get_contents($this->cacheFile);

        if ($json === false || $json === '') {
            throw new CalendarUnavailableException('Failed to read cached calendar data');
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['events'])) {
            throw new CalendarUnavailableException('Invalid cached calendar data');
        }

        return $data;
    }
}
