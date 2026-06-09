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
        private ?CalendarRepository $fallback = null,
    ) {
    }

    public function getEvents(): array
    {
        $data = $this->readFromCache();

        if ($data !== null) {
            return $data;
        }

        // Cache miss — try the fallback (GitHub), then persist for future requests.
        if ($this->fallback !== null) {
            $data = $this->fallback->getEvents();
            $this->writeToCache($data);

            return $data;
        }

        throw new CalendarUnavailableException(
            'Calendar data not yet available — please try again shortly.',
        );
    }

    /**
     * @return array{events: array}|null
     */
    private function readFromCache(): ?array
    {
        if (!is_file($this->cacheFile)) {
            return null;
        }

        $json = file_get_contents($this->cacheFile);

        if ($json === false || $json === '') {
            return null;
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['events'])) {
            return null;
        }

        return $data;
    }

    private function writeToCache(array $data): void
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            return;
        }

        $tmpFile = $this->cacheFile . '.tmp.' . getmypid();

        if (file_put_contents($tmpFile, $json, LOCK_EX) !== false) {
            @rename($tmpFile, $this->cacheFile);
        }
    }
}
