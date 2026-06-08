<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Infrastructure;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SportClimbing\Application\ServeCalendarUseCase;
use SportClimbing\Application\TrackDownloadUseCase;

final readonly class CalendarController
{
    public function __construct(
        private ServeCalendarUseCase $serveCalendar,
        private TrackDownloadUseCase $trackDownload,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        // Convert query params to strings (Slim PSR-7 may return arrays for multi-value keys,
        // but we only accept scalar query params)
        /** @var array<string, string> $params */
        $params = array_map(
            fn (mixed $v): string => is_array($v) ? implode(',', $v) : (string) $v,
            $queryParams,
        );

        $result = $this->serveCalendar->execute($params);

        // Build PSR-7 response from DTO
        $response = $response->withStatus($result->response->status);

        foreach ($result->response->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $response->getBody()->write($result->response->body);

        // Fire-and-forget tracking after response is sent
        if ($result->tracking !== null) {
            $this->trackDownload->execute(
                $result->tracking->format,
                $result->tracking->filterParams,
            );
        }

        return $response;
    }
}
