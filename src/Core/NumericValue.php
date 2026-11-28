<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

// ── Core: NumericValue ───────────────────────────────────────────────
// Reads numbers out of values, integers kept as integers.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads ints, finite floats and numeric strings as numbers, keeping
 * integers as ints (" 12 " is 12; "1.5", "1e3" and "012" are floats);
 * booleans, INF, NAN and other strings are not numbers. integer() reads
 * the integral ones as int, when they fit: 42.0 and "1e3" do, 1.5 and
 * 1e30 do not.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     NumericValue::integer('1e3'); // 1000
 *
 * References: PHP manual, numeric strings; ADR-008; SPEC-002 §5.
 */
final class NumericValue
{
    public static function of(mixed $value): int|float|null
    {
        if (is_string($value) && is_numeric($value)) {
            $value = filter_var(value: $value, filter: FILTER_VALIDATE_INT, options: FILTER_NULL_ON_FAILURE) ?? (float) $value;
        }

        if (is_int($value) || (is_float($value) && is_finite($value))) {
            return $value;
        }

        return null;
    }

    public static function integer(mixed $value): ?int
    {
        $number = self::of($value);

        if (is_float($number) && fmod($number, 1.0) === 0.0 && $number >= -9.2233720368547758E18 && $number < 9.2233720368547758E18) {
            return (int) $number;
        }

        return is_int($number) ? $number : null;
    }
}
