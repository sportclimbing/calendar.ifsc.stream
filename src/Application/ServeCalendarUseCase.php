<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Application;

use SportClimbing\IcsGenerator\CalendarFilter;
use SportClimbing\IcsGenerator\FilterParams;
use SportClimbing\Port\CalendarGenerator;
use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\CalendarUnavailableException;

final readonly class ServeCalendarUseCase
{
    public function __construct(
        private CalendarRepository $calendarRepository,
        private CalendarGenerator $calendarGenerator,
        private InputValidator $inputValidator,
    ) {
    }

    /**
     * @param array<string, string> $queryParams
     */
    public function execute(array $queryParams): ServeCalendarResult
    {
        $errors = $this->inputValidator->validate($queryParams);

        if ($errors !== []) {
            return new ServeCalendarResult(
                new ResponseDto(
                    body: implode("\n", $errors) . "\n",
                    headers: ['Content-Type' => 'text/plain; charset=utf-8'],
                    status: 400,
                ),
                tracking: null,
            );
        }

        $filterParams = FilterParams::fromQuery($queryParams);
        $hasFilters = !$filterParams->isEmpty();

        try {
            $events = $this->calendarRepository->getEvents()['events'];

            if ($hasFilters) {
                $events = CalendarFilter::apply($events, $filterParams);
            }

            $ics = $this->calendarGenerator->generateForEvents($events);

            return new ServeCalendarResult(
                new ResponseDto(
                    body: $ics,
                    headers: [
                        'Content-Disposition' => 'attachment; filename="ifsc-calendar.ics"',
                        'Content-Type' => 'text/calendar; charset=utf-8',
                        'Content-Length' => (string) strlen($ics),
                    ],
                    status: 200,
                ),
                tracking: new TrackDownload('ics', $hasFilters ? $filterParams : null),
            );
        } catch (CalendarUnavailableException) {
            return new ServeCalendarResult(
                new ResponseDto(
                    body: "Service temporarily unavailable\n",
                    headers: [],
                    status: 503,
                ),
                tracking: null,
            );
        }
    }
}
