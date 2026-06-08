<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Adapter;

use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\CalendarUnavailableException;

final class CachingCalendarRepository implements CalendarRepository
{
    private const LOCK_EX = 2;

    public function __construct(
        private readonly CalendarRepository $inner,
        private readonly string $cacheDir,
        private readonly int $cacheSeconds,
    ) {
    }

    public function getIcs(): string
    {
        $cacheFile = $this->cacheDir . '/calendar.ics';

        return $this->cached(
            cacheFile: $cacheFile,
            fetch: fn () => $this->inner->getIcs(),
            onFresh: fn () => file_get_contents($cacheFile),
        );
    }

    public function getEvents(): array
    {
        $cacheFile = $this->cacheDir . '/calendar.json';

        $json = $this->cached(
            cacheFile: $cacheFile,
            fetch: fn () => json_encode($this->inner->getEvents(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            onFresh: fn () => file_get_contents($cacheFile),
        );

        if ($json === '') {
            throw new CalendarUnavailableException('Failed to read cached calendar data');
        }

        $data = json_decode($json, true);

        if (!is_array($data) || !isset($data['events'])) {
            throw new CalendarUnavailableException('Invalid cached calendar data');
        }

        return $data;
    }

    private function cached(string $cacheFile, callable $fetch, callable $onFresh): string
    {
        $exists = is_file($cacheFile);

        if ($exists && (time() - filemtime($cacheFile)) < $this->cacheSeconds) {
            $contents = $onFresh();
            if ($contents !== false && $contents !== '') {
                return $contents;
            }
        }

        $contents = $fetch();

        if ($contents === false || $contents === '') {
            throw new CalendarUnavailableException('Failed to fetch calendar data');
        }

        if (file_put_contents($cacheFile, $contents, self::LOCK_EX) === false) {
            throw new CalendarUnavailableException('Failed to write cache file');
        }

        touch($cacheFile);

        return $contents;
    }
}
