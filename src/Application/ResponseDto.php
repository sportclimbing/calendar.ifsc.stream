<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Application;

final readonly class ResponseDto
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public string $body,
        public array $headers,
        public int $status,
    ) {
    }
}
