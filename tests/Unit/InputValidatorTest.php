<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SportClimbing\Application\InputValidator;

class InputValidatorTest extends TestCase
{
    private InputValidator $validator;

    private const ALLOWED = [
        'discipline' => ['boulder', 'lead', 'speed'],
        'kind' => ['qualification', 'semi-final', 'final'],
        'category' => ['men', 'women'],
    ];

    protected function setUp(): void
    {
        $this->validator = new InputValidator(self::ALLOWED);
    }

    public function testEmptyParamsReturnsNoErrors(): void
    {
        $this->assertSame([], $this->validator->validate([]));
    }

    public function testValidSingleValueReturnsNoErrors(): void
    {
        $this->assertSame([], $this->validator->validate(['discipline' => 'boulder']));
        $this->assertSame([], $this->validator->validate(['kind' => 'final']));
        $this->assertSame([], $this->validator->validate(['category' => 'men']));
    }

    public function testValidMultipleValuesReturnsNoErrors(): void
    {
        $this->assertSame([], $this->validator->validate([
            'discipline' => 'boulder,lead',
        ]));

        $this->assertSame([], $this->validator->validate([
            'discipline' => 'boulder,lead,speed',
        ]));
    }

    public function testValidMultipleParamsReturnsNoErrors(): void
    {
        $this->assertSame([], $this->validator->validate([
            'discipline' => 'boulder',
            'kind' => 'final',
            'category' => 'men',
        ]));
    }

    public function testInvalidValueReturnsError(): void
    {
        $errors = $this->validator->validate(['discipline' => 'invalid']);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Invalid value "invalid"', $errors[0]);
        $this->assertStringContainsString('discipline', $errors[0]);
    }

    public function testInvalidKindReturnsError(): void
    {
        $errors = $this->validator->validate(['kind' => 'quarter-final']);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('quarter-final', $errors[0]);
        $this->assertStringContainsString('kind', $errors[0]);
    }

    public function testInvalidCategoryReturnsError(): void
    {
        $errors = $this->validator->validate(['category' => 'mixed']);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('mixed', $errors[0]);
    }

    public function testMixedValidAndInvalidReturnsMultipleErrors(): void
    {
        $errors = $this->validator->validate([
            'discipline' => 'boulder,invalid',
            'kind' => 'wrong',
        ]);

        $this->assertCount(2, $errors);
    }

    public function testUnknownParameterIsIgnored(): void
    {
        $this->assertSame([], $this->validator->validate([
            'unknown' => 'value',
            'foo' => 'bar',
        ]));
    }

    public function testSpeedRelayIsRejected(): void
    {
        $errors = $this->validator->validate(['discipline' => 'speed_relay']);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('speed_relay', $errors[0]);
    }

    public function testWhitespaceIsTrimmed(): void
    {
        $errors = $this->validator->validate(['discipline' => ' boulder , lead ']);

        $this->assertSame([], $errors);
    }

    public function testEmptyValueIsIgnored(): void
    {
        $this->assertSame([], $this->validator->validate(['discipline' => '']));
    }

    public function testMultipleInvalidValuesInOneParam(): void
    {
        $errors = $this->validator->validate([
            'discipline' => 'boulder,foo,bar',
        ]);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('foo', $errors[0]);
        $this->assertStringContainsString('bar', $errors[1]);
    }

    public function testErrorShowsAllowedValues(): void
    {
        $errors = $this->validator->validate(['discipline' => 'nope']);

        $this->assertStringContainsString('boulder, lead, speed', $errors[0]);
    }
}
