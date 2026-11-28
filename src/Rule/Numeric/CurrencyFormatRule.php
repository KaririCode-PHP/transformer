<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Numeric;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\IntegerParameter;
use KaririCode\Transformer\Core\NumericValue;

// ── Rule: CurrencyFormatRule ─────────────────────────────────────────
// Writes a number as an amount of money.
// ─────────────────────────────────────────────────────────────────────

/**
 * Writes numbers and numeric strings with 'decimals' places (2 unless
 * set), 'dec_point' ("." unless set) and 'thousands' ("," unless set),
 * between 'prefix' and 'suffix' (both empty unless set), with the minus
 * sign before the prefix: -1234.5 becomes "-R$ 1.234,50" with
 * prefix "R$ ", dec_point "," and thousands ".". An amount that rounds
 * to zero has no sign. Non-numbers are returned unchanged.
 *
 * Preconditions: 'decimals' is an integer of 0 or more; 'dec_point', 'thousands', 'prefix' and 'suffix' are strings.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['price' => [['currency_format', ['prefix' => 'R$ ', 'dec_point' => ',', 'thousands' => '.']]]]
 *
 * References: PHP number_format(); SPEC-002 §5.
 */
final readonly class CurrencyFormatRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $decimals = IntegerParameter::atLeast(context: $context, name: 'decimals', default: 2, minimum: 0);
        $point = $context->stringParameter('dec_point', '.');
        $thousands = $context->stringParameter('thousands', ',');
        $prefix = $context->stringParameter('prefix', '');
        $suffix = $context->stringParameter('suffix', '');
        $number = NumericValue::of($value);

        if ($number === null) {
            return $value;
        }

        $rounded = round($number, $decimals);
        $amount = number_format(num: abs($rounded), decimals: $decimals, decimal_separator: $point, thousands_separator: $thousands);

        return ($rounded < 0 ? '-' : '') . $prefix . $amount . $suffix;
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.currency_format';
    }
}
