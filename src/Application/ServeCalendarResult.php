<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Application;

final readonly class ServeCalendarResult
{
    public function __construct(
        public ResponseDto $response,
        public ?TrackDownload $tracking,
    ) {
    }
}
