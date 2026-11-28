<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Core: IntegerParameter ───────────────────────────────────────────
// Reads an integer parameter that has a lower bound.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads an int parameter with the context's typed reader and refuses
 * values below the minimum: counts, lengths and decimal places are never
 * negative, and a negative one is the application's mistake. depth()
 * reads the JSON nesting limit within the range PHP accepts.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     IntegerParameter::atLeast($context, 'decimals', 2, 0);
 *
 * References: ADR-004; SPEC-001 §4.
 */
final class IntegerParameter
{
    public static function atLeast(TransformationContext $context, string $name, int $default, int $minimum): int
    {
        $value = $context->intParameter($name, $default);

        return $value >= $minimum
            ? $value
            : throw InvalidRuleException::invalidParameter(field: $context->field, parameter: $name, expected: "an integer of {$minimum} or more", given: (string) $value);
    }

    /**
     * Reads 'depth' (512 unless set), the nesting limit json_encode() and
     * json_decode() take, which PHP bounds to 1–2147483647.
     *
     * @return int<1, 2147483647>
     */
    public static function depth(TransformationContext $context): int
    {
        $depth = $context->intParameter('depth', 512);

        return $depth >= 1 && $depth <= 2_147_483_647
            ? $depth
            : throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'depth', expected: 'an integer from 1 to 2147483647', given: (string) $depth);
    }
}
