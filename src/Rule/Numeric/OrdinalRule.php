<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Numeric;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\NumericValue;

// ── Rule: OrdinalRule ────────────────────────────────────────────────
// Writes an integer as an English ordinal.
// ─────────────────────────────────────────────────────────────────────

/**
 * Appends the English ordinal suffix to integers and integral numeric
 * strings: 1st, 2nd, 3rd, 4th, 11th, 12th, 13th, 21st, 112th; negative
 * numbers keep their sign (-1st). Fractions and non-numbers are
 * returned unchanged.
 *
 * Preconditions: none; other values are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['position' => ['ordinal']]
 *
 * References: SPEC-002 §5.
 */
final readonly class OrdinalRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $number = NumericValue::integer($value);

        if ($number === null) {
            return $value;
        }

        $lastTwo = abs($number % 100);
        $suffix = match (true) {
            $lastTwo >= 11 && $lastTwo <= 13 => 'th',
            $lastTwo % 10 === 1 => 'st',
            $lastTwo % 10 === 2 => 'nd',
            $lastTwo % 10 === 3 => 'rd',
            default => 'th',
        };

        return $number . $suffix;
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.ordinal';
    }
}
