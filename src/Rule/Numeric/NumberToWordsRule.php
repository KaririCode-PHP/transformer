<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Numeric;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\NumericValue;

// ── Rule: NumberToWordsRule ──────────────────────────────────────────
// Writes an integer in English words.
// ─────────────────────────────────────────────────────────────────────

/**
 * Writes integers and integral numeric strings in English words, short
 * scale, with "and" before the last two digits in the British way: 115
 * becomes "one hundred and fifteen", 1001 "one thousand and one",
 * -2500000 "minus two million five hundred thousand". Every int but
 * PHP_INT_MIN is covered; fractions, PHP_INT_MIN and non-numbers are
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
 *     ['amount_in_words' => ['number_to_words']]
 *
 * References: SPEC-002 §5.
 */
final readonly class NumberToWordsRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $number = NumericValue::integer($value);

        if ($number === null || $number === PHP_INT_MIN) {
            return $value;
        }

        $magnitude = abs($number);

        return ($number < 0 ? 'minus ' : '') . ($magnitude === 0 ? 'zero' : self::words($magnitude));
    }

    #[\Override]
    public function name(): string
    {
        return 'numeric.number_to_words';
    }

    /** @var list<string> */
    private const array ONES = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];

    /** @var list<string> */
    private const array TENS = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

    /** @var list<string> short-scale names of each power of 1000 an int reaches */
    private const array SCALES = ['', ' thousand', ' million', ' billion', ' trillion', ' quadrillion', ' quintillion'];

    /**
     * @param positive-int $number
     */
    private static function words(int $number): string
    {
        $groups = [];
        $rest = $number;

        foreach (self::SCALES as $scale => $name) {
            /** @var int<0, 999> $group */
            $group = $rest % 1000;
            $rest = intdiv($rest, 1000);

            if ($group > 0) {
                $and = $scale === 0 && $group < 100 && $rest > 0 ? 'and ' : '';
                $groups[] = $and . self::hundreds($group) . $name;
            }
        }

        return implode(' ', array_reverse($groups));
    }

    /**
     * @param int<1, 999> $group
     */
    private static function hundreds(int $group): string
    {
        $hundreds = $group >= 100 ? self::ONES[intdiv($group, 100)] . ' hundred' : '';
        $rest = $group % 100;

        if ($rest === 0) {
            return $hundreds;
        }

        $tens = $rest < 20 ? self::ONES[$rest] : self::TENS[intdiv($rest, 10)] . ($rest % 10 > 0 ? '-' . self::ONES[$rest % 10] : '');

        return $hundreds === '' ? $tens : $hundreds . ' and ' . $tens;
    }
}
