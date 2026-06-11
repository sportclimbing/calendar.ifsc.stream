<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SportClimbing\Application\InputValidator;
use SportClimbing\Application\ServeCalendarUseCase;
use SportClimbing\Port\CalendarGenerator;
use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\CalendarUnavailableException;

class ServeCalendarUseCaseTest extends TestCase
{
    private CalendarRepository $repository;
    private CalendarGenerator $generator;
    private InputValidator $validator;

    private const array ALLOWED_VALUES = [
        'discipline' => ['boulder', 'lead', 'speed'],
        'kind' => ['qualification', 'semi-final', 'final'],
        'category' => ['men', 'women'],
        'series' => ['world', 'para'],
    ];

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CalendarRepository::class);
        $this->generator = $this->createMock(CalendarGenerator::class);
        $this->validator = new InputValidator(self::ALLOWED_VALUES);
    }

    public function testNoFiltersGeneratesFullIcs(): void
    {
        $events = [['id' => 1, 'name' => 'Test Event', 'rounds' => []]];

        $this->repository->expects($this->once())
            ->method('getEvents')
            ->willReturn(['events' => $events]);

        $this->generator->expects($this->once())
            ->method('generateForEvents')
            ->with($events)
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

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

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

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
            ->method('getEvents')
            ->willThrowException(new CalendarUnavailableException('Down'));

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute([]);

        $this->assertSame(503, $result->response->status);
        $this->assertStringContainsString('unavailable', $result->response->body);
        $this->assertNull($result->tracking);
    }

    public function testTrackingIsSetForIcs(): void
    {
        $this->repository->expects($this->once())
            ->method('getEvents')
            ->willReturn(['events' => []]);

        $this->generator->expects($this->once())
            ->method('generateForEvents')
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute([]);

        $this->assertNotNull($result->tracking);
        $this->assertSame('ics', $result->tracking->format);
        $this->assertNull($result->tracking->filterParams); // no filters → null
    }

    public function testTrackingHasFilterParamsWhenFiltered(): void
    {
        $this->repository->expects($this->once())
            ->method('getEvents')
            ->willReturn(['events' => []]);

        $this->generator->expects($this->once())
            ->method('generateForEvents')
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute(['discipline' => 'boulder']);

        $this->assertNotNull($result->tracking);
        $this->assertNotNull($result->tracking->filterParams);
        $this->assertSame(['boulder'], $result->tracking->filterParams->disciplines);
    }

    public function testTrackingIsNullOn503(): void
    {
        $this->repository->expects($this->once())
            ->method('getEvents')
            ->willThrowException(new CalendarUnavailableException('Down'));

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute([]);

        $this->assertNull($result->tracking);
    }

    public function testInvalidDisciplineReturns400(): void
    {
        $this->repository->expects($this->never())
            ->method('getEvents');

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute(['discipline' => 'invalid']);

        $this->assertSame(400, $result->response->status);
        $this->assertStringContainsString('Invalid value "invalid"', $result->response->body);
        $this->assertStringContainsString('discipline', $result->response->body);
        $this->assertNull($result->tracking);
    }

    public function testInvalidKindReturns400(): void
    {
        $this->repository->expects($this->never())
            ->method('getEvents');

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute(['kind' => 'quarter-final']);

        $this->assertSame(400, $result->response->status);
        $this->assertStringContainsString('Invalid value "quarter-final"', $result->response->body);
        $this->assertStringContainsString('kind', $result->response->body);
    }

    public function testInvalidCategoryReturns400(): void
    {
        $this->repository->expects($this->never())
            ->method('getEvents');

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute(['category' => 'mixed']);

        $this->assertSame(400, $result->response->status);
        $this->assertStringContainsString('Invalid value "mixed"', $result->response->body);
    }

    public function testMixedValidAndInvalidReturns400(): void
    {
        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute([
            'discipline' => 'boulder,invalid',
        ]);

        $this->assertSame(400, $result->response->status);
        $this->assertStringContainsString('Invalid value "invalid"', $result->response->body);
    }

    public function testUnknownParameterIsIgnored(): void
    {
        $this->repository->expects($this->once())
            ->method('getEvents')
            ->willReturn(['events' => []]);

        $this->generator->expects($this->once())
            ->method('generateForEvents')
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute(['unknown' => 'value']);

        $this->assertSame(200, $result->response->status);
    }

    public function testValidDisciplinePasses(): void
    {
        $this->repository->expects($this->once())
            ->method('getEvents')
            ->willReturn(['events' => []]);

        $this->generator->expects($this->once())
            ->method('generateForEvents')
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute(['discipline' => 'boulder,speed']);

        $this->assertSame(200, $result->response->status);
    }

    public function testSpeedRelayIsRejected(): void
    {
        $this->repository->expects($this->never())
            ->method('getEvents');

        $useCase = new ServeCalendarUseCase($this->repository, $this->generator, $this->validator);

        $result = $useCase->execute(['discipline' => 'speed_relay']);

        $this->assertSame(400, $result->response->status);
        $this->assertStringContainsString('Invalid value "speed_relay"', $result->response->body);
    }
}
