<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Application;

use SportClimbing\IcsGenerator\FilterParams;
use SportClimbing\Port\AnalyticsClient;

final readonly class TrackDownloadUseCase
{
    public function __construct(
        private AnalyticsClient $analyticsClient,
    ) {
    }

    public function execute(string $format, ?FilterParams $filterParams): void
    {
        $this->analyticsClient->trackDownload($format, $filterParams);
    }
}
