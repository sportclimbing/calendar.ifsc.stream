<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Adapter;

use Exception;
use SportClimbing\IcsGenerator\IcsGenerator;
use SportClimbing\Port\CalendarGenerator;

final readonly class SportClimbingIcsGenerator implements CalendarGenerator
{
    public function __construct(
        private IcsGenerator $icsGenerator,
    ) {
    }

    /**
     * @throws Exception
     */
    public function generateForEvents(array $events): string
    {
        return $this->icsGenerator->generateForEvents($events);
    }
}
