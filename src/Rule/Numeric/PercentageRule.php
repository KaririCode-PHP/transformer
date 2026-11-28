<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Numeric;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\IntegerParameter;
use KaririCode\Transformer\Core\NumericValue;

// ── Rule: PercentageRule ─────────────────────────────────────────────
// Writes a ratio as a percentage.
// ─────────────────────────────────────────────────────────────────────

/**
 * Multiplies numbers and numeric strings by 100 and writes them with
 * 'decimals' places (2 unless set), 'dec_point' ("." unless set) and
 * 'thousands' ("," unless set), followed by 'suffix' ("%" unless set):
 * 0.125 becomes "12.50%". A value that rounds to zero has no sign.
 * Non-numbers are returned unchanged.
 *
 * Preconditions: 'decimals' is an integer of 0 or more; 'dec_point', 'thousands' and 'suffix' are strings.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['conversion' => [['percentage', ['decimals' => 1, 'dec_point' => ',']]]]
 *
 * References: PHP number_format(); SPEC-002 §5.
 */
final readonly class PercentageRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $decimals = IntegerParameter::atLeast(context: $context, name: 'decimals', default: 2, minimum: 0);
        $point = $context->stringParameter('dec_point', '.');
        $thousands = $context->stringParameter('thousands', ',');
        $suffix = $context->stringParameter('suffix', '%');
        $number = NumericValue::of($value);

        if ($number === null) {
            return $value;
        }

        $rounded = round((float) $number * 100.0, $decimals);
        $text = number_format(num: abs($rounded), decimals: $decimals, decimal_separator: $point, thousands_separator: $thousands);

        return ($rounded < 0 ? '-' : '') . $text . $suffix;
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.percentage';
    }
}
