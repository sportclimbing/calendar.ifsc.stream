<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Application;

use SportClimbing\IcsGenerator\FilterParams;

final readonly class TrackDownload
{
    public function __construct(
        public string $format,
        public ?FilterParams $filterParams,
    ) {
    }
}
