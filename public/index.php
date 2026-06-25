<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Factory\AppFactory;
use SportClimbing\IcsGenerator\CalendarFactory;
use SportClimbing\IcsGenerator\IcsGenerator;
use SportClimbing\Adapter\FileGetContentsHttpClient;
use SportClimbing\Adapter\GitHubCalendarRepository;
use SportClimbing\Adapter\GoogleAnalyticsAdapter;
use SportClimbing\Adapter\LocalCalendarRepository;
use SportClimbing\Adapter\SportClimbingIcsGenerator;
use SportClimbing\Application\InputValidator;
use SportClimbing\Application\ServeCalendarUseCase;
use SportClimbing\Application\TrackDownloadUseCase;
use SportClimbing\Infrastructure\CalendarController;
use SportClimbing\Port\AnalyticsClient;
use SportClimbing\Port\CalendarGenerator;
use SportClimbing\Port\CalendarRepository;
use SportClimbing\Port\HttpClient;

require __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../config/settings.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    'cache.dir' => $settings['cache']['dir'],
    'cache.file' => $settings['cache']['dir'] . '/calendar.json',
    'calendar.json_url' => $settings['calendar']['base_url'] . '/' . $settings['calendar']['json_filename'],

    // Ports → Adapters
    HttpClient::class => fn () => new FileGetContentsHttpClient(),

    CalendarRepository::class => function (ContainerInterface $c) {
        $fallback = new GitHubCalendarRepository(
            $c->get(HttpClient::class),
            $c->get('calendar.json_url'),
        );

        return new LocalCalendarRepository($c->get('cache.file'), $fallback);
    },

    AnalyticsClient::class => fn () => new GoogleAnalyticsAdapter(
        $settings['analytics']['measurement_id'],
        $settings['analytics']['api_secret'],
    ),

    // Domain service from ifsc-ics-generator
    CalendarGenerator::class => fn () => new SportClimbingIcsGenerator(
        new IcsGenerator(
            new CalendarFactory(),
            $settings['calendar']['prod_id'],
            $settings['calendar']['duration'],
            $settings['calendar']['cal_name'],
        ),
    ),

    // Use cases
    ServeCalendarUseCase::class => function (ContainerInterface $c) use ($settings) {
        $validator = new InputValidator($settings['validation']);

        return new ServeCalendarUseCase(
            $c->get(CalendarRepository::class),
            $c->get(CalendarGenerator::class),
            $validator,
        );
    },

    TrackDownloadUseCase::class => function (ContainerInterface $c) {
        return new TrackDownloadUseCase(
            $c->get(AnalyticsClient::class),
        );
    },

    // Controller
    CalendarController::class => function (ContainerInterface $c) {
        return new CalendarController(
            $c->get(ServeCalendarUseCase::class),
            $c->get(TrackDownloadUseCase::class),
        );
    },
]);

$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->get('/', CalendarController::class);
$app->run();
