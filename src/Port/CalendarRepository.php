<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Port;

interface CalendarRepository
{
    /**
     * @throws CalendarUnavailableException
     */
    public function getIcs(): string;

    /**
     * Returns the full decoded JSON structure (including 'events' key).
     *
     * @return array{events: array}
     *
     * @throws CalendarUnavailableException
     */
    public function getEvents(): array;
}
