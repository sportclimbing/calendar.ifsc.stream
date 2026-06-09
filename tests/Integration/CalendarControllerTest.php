<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Tests\Integration;

use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use SportClimbing\Application\InputValidator;
use SportClimbing\Application\ServeCalendarUseCase;
use SportClimbing\Application\TrackDownloadUseCase;
use SportClimbing\Infrastructure\CalendarController;
use SportClimbing\Port\AnalyticsClient;
use SportClimbing\Port\CalendarGenerator;
use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\CalendarUnavailableException;

class CalendarControllerTest extends TestCase
{
    private App $app;
    private InputValidator $validator;

    private const ALLOWED_VALUES = [
        'discipline' => ['boulder', 'lead', 'speed'],
        'kind' => ['qualification', 'semi-final', 'final'],
        'category' => ['men', 'women'],
    ];

    protected function setUp(): void
    {
        // Minimal DI container with mocked dependencies
        $containerBuilder = new ContainerBuilder();

        $repository = $this->createMock(CalendarRepository::class);
        $generator = $this->createMock(CalendarGenerator::class);
        $analytics = $this->createMock(AnalyticsClient::class);

        $this->validator = new InputValidator(self::ALLOWED_VALUES);

        $containerBuilder->addDefinitions([
            CalendarRepository::class => $repository,
            CalendarGenerator::class => $generator,
            AnalyticsClient::class => $analytics,
            ServeCalendarUseCase::class => fn () => new ServeCalendarUseCase($repository, $generator, $this->validator),
            TrackDownloadUseCase::class => fn () => new TrackDownloadUseCase($analytics),
            CalendarController::class => fn () => new CalendarController(
                new ServeCalendarUseCase($repository, $generator, $this->validator),
                new TrackDownloadUseCase($analytics),
            ),
        ]);

        $container = $containerBuilder->build();

        AppFactory::setContainer($container);
        $this->app = AppFactory::create();

        $this->app->get('/', CalendarController::class);
    }

    public function testControllerReturns503OnFailure(): void
    {
        $repository = $this->createMock(CalendarRepository::class);
        $repository->method('getEvents')
            ->willThrowException(new CalendarUnavailableException('Down'));

        $generator = $this->createMock(CalendarGenerator::class);
        $analytics = $this->createMock(AnalyticsClient::class);

        $controller = new CalendarController(
            new ServeCalendarUseCase($repository, $generator, $this->validator),
            new TrackDownloadUseCase($analytics),
        );

        $request = ServerRequestFactory::createFromGlobals();
        $response = new Response();

        $response = $controller($request, $response);

        $this->assertSame(503, $response->getStatusCode());
    }

    public function testControllerReturns200ForIcs(): void
    {
        $repository = $this->createMock(CalendarRepository::class);
        $repository->method('getEvents')
            ->willReturn(['events' => []]);

        $generator = $this->createMock(CalendarGenerator::class);
        $generator->method('generateForEvents')
            ->willReturn("BEGIN:VCALENDAR\r\nEND:VCALENDAR\r\n");

        $analytics = $this->createMock(AnalyticsClient::class);
        $analytics->method('trackDownload'); // noop

        $controller = new CalendarController(
            new ServeCalendarUseCase($repository, $generator, $this->validator),
            new TrackDownloadUseCase($analytics),
        );

        $request = ServerRequestFactory::createFromGlobals();
        $response = new Response();

        $response = $controller($request, $response);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/calendar', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('BEGIN:VCALENDAR', (string) $response->getBody());
    }
}
