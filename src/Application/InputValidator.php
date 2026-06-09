<?php declare(strict_types=1);

/**
 * @license  http://opensource.org/licenses/mit-license.php MIT
 * @link     https://github.com/nicoSWD
 * @author   Nicolas Oelgart <nico@ifsc.stream>
 */
namespace SportClimbing\Application;

final readonly class InputValidator
{
    /**
     * @param array<string, string[]> $allowedValues Map of parameter name to allowed values
     */
    public function __construct(
        private array $allowedValues,
    ) {}

    /**
     * Validate query parameters against allowed-value whitelists.
     *
     * Unknown parameters are silently ignored. Known parameters are
     * comma-split and each value is checked against the whitelist.
     *
     * @param array<string, string> $params
     * @return string[] List of error messages (empty if valid)
     */
    public function validate(array $params): array
    {
        $errors = [];

        foreach ($params as $key => $value) {
            if (!isset($this->allowedValues[$key])) {
                // Unknown parameter — silently ignored
                continue;
            }

            $values = $this->splitAndTrim($value);

            foreach ($values as $v) {
                if (!in_array($v, $this->allowedValues[$key], true)) {
                    $allowed = implode(', ', $this->allowedValues[$key]);
                    $errors[] = "Invalid value \"{$v}\" for parameter \"{$key}\". Allowed values: {$allowed}";
                }
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function splitAndTrim(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode(',', $value)),
            static fn (string $v): bool => $v !== '',
        ));
    }
}
