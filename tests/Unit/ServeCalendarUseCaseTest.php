<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SportClimbing\Application\ServeCalendarUseCase;
use SportClimbing\Port\CalendarGenerator;
use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\CalendarUnavailableException;

class ServeCalendarUseCaseTest extends TestCase
{
    private CalendarRepository $repository;
    private CalendarGenerator $generator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CalendarRepository::class);
        $this->generator = $this->createMock(CalendarGenerator::class);
    }

    public function testNoFiltersReturnsCachedIcs(): void
    {
        $this->repository->expects($this->once())
            ->method('getIcs')
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator);

        $result = $useCase->execute([]);

        $this->assertSame(200, $result->response->status);
        $this->assertStringContainsString('text/calendar', $result->response->headers['Content-Type']);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $result->response->body);
    }

    public function testFilteredIcsRegeneratesFromJson(): void
    {
        $events = [
            [
                'id' => 1,
                'name' => 'Boulder World Cup',
                'rounds' => [
                    [
                        'disciplines' => ['boulder'],
                        'kind' => 'final',
                        'categories' => ['men'],
                        'start_date' => '2025-06-01T10:00:00+02:00',
                        'end_date' => '2025-06-01T12:00:00+02:00',
                        'stream_url' => 'https://example.com/stream',
                    ],
                ],
            ],
            [
                'id' => 2,
                'name' => 'Lead World Cup',
                'rounds' => [
                    [
                        'disciplines' => ['lead'],
                        'kind' => 'final',
                        'categories' => ['men'],
                        'start_date' => '2025-06-01T10:00:00+02:00',
                        'end_date' => '2025-06-01T12:00:00+02:00',
                        'stream_url' => null,
                    ],
                ],
            ],
        ];

        $this->repository->expects($this->once())
            ->method('getEvents')
            ->willReturn(['events' => $events]);

        $this->generator->expects($this->once())
            ->method('generateForEvents')
            ->willReturn("BEGIN:VCALENDAR\r\nFILTERED\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator);

        $result = $useCase->execute([
            'format' => 'ics',
            'discipline' => 'boulder',
            'kind' => 'final',
            'category' => 'men',
        ]);

        $this->assertSame(200, $result->response->status);
        $this->assertStringContainsString('FILTERED', $result->response->body);
        $this->assertStringContainsString('text/calendar', $result->response->headers['Content-Type']);
    }

    public function testRepositoryFailureReturns503(): void
    {
        $this->repository->expects($this->once())
            ->method('getIcs')
            ->willThrowException(new CalendarUnavailableException('Down'));

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator);

        $result = $useCase->execute([]);

        $this->assertSame(503, $result->response->status);
        $this->assertStringContainsString('unavailable', $result->response->body);
        $this->assertNull($result->tracking);
    }

    public function testTrackingIsSetForIcs(): void
    {
        $this->repository->expects($this->once())
            ->method('getIcs')
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator);

        $result = $useCase->execute([]);

        $this->assertNotNull($result->tracking);
        $this->assertSame('ics', $result->tracking->format);
    }

    public function testTrackingIsNullOn503(): void
    {
        $this->repository->expects($this->once())
            ->method('getIcs')
            ->willThrowException(new CalendarUnavailableException('Down'));

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator);

        $result = $useCase->execute([]);

        $this->assertNull($result->tracking);
    }
}
