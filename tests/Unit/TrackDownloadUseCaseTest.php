<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SportClimbing\IcsGenerator\FilterParams;
use SportClimbing\Application\TrackDownloadUseCase;
use SportClimbing\Port\AnalyticsClient;

class TrackDownloadUseCaseTest extends TestCase
{
    public function testExecuteCallsAnalyticsClient(): void
    {
        $analytics = $this->createMock(AnalyticsClient::class);
        $analytics->expects($this->once())
            ->method('trackDownload')
            ->with('ics', null);

        $useCase = new TrackDownloadUseCase($analytics);
        $useCase->execute('ics', null);
    }

    public function testExecutePassesFilterParams(): void
    {
        $filters = new FilterParams(
            disciplines: ['boulder'],
            kinds: ['final'],
            categories: ['men'],
        );

        $analytics = $this->createMock(AnalyticsClient::class);
        $analytics->expects($this->once())
            ->method('trackDownload')
            ->with('ics', $filters);

        $useCase = new TrackDownloadUseCase($analytics);
        $useCase->execute('ics', $filters);
    }
}
