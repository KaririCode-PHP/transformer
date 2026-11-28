<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Date;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\DateValue;

// ── Rule: AgeRule ────────────────────────────────────────────────────
// Reads a date of birth as an age in whole years.
// ─────────────────────────────────────────────────────────────────────

/**
 * Parses the string strictly in 'from' (Y-m-d unless set) in 'timezone'
 * (UTC unless set) and returns the whole years from that date to 'now'
 * (the current time unless a DateTimeInterface is given). Dates after
 * 'now' and strings that are not a real date are returned unchanged.
 *
 * Preconditions: 'from' uses DateTimeImmutable::createFromFormat() letters; 'timezone' is an identifier DateTimeZone accepts; 'now', when given, is a DateTimeInterface.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['birth_date' => ['age']]; // '1990-03-07' → 36 on 2026-11-28
 *
 * References: ADR-008; SPEC-002 §4.
 */
final readonly class AgeRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $from = $context->stringParameter('from', 'Y-m-d');
        $zone = DateValue::zone($context);
        $now = DateValue::now($context, $zone);
        $date = is_string($value) ? DateValue::parse(value: $value, format: $from, zone: $zone) : null;

        return $date === null || $date > $now ? $value : $date->diff($now)->y;
    }

    #[\Override]
    public function name(): string
    {
        return 'date.age';
    }
}
