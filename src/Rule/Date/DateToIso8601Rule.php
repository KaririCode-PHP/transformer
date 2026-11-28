<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Date;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\DateValue;

// ── Rule: DateToIso8601Rule ──────────────────────────────────────────
// Writes a date in ISO 8601, with its offset.
// ─────────────────────────────────────────────────────────────────────

/**
 * Parses the string strictly in 'from' (d/m/Y unless set), in
 * 'timezone' (UTC unless set) when the text names no zone of its own,
 * and writes it as ISO 8601 with the offset (DATE_ATOM,
 * 2026-11-28T00:00:00+00:00). A date that names its own offset keeps
 * it. Strings that are not a real date in the format are returned
 * unchanged.
 *
 * Preconditions: 'from' uses DateTimeImmutable::createFromFormat() letters; 'timezone' is an identifier DateTimeZone accepts.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['birth' => [['date_to_iso8601', ['timezone' => 'America/Sao_Paulo']]]]
 *
 * References: ISO 8601-1:2019; RFC 3339; ADR-008; SPEC-002 §4.
 */
final readonly class DateToIso8601Rule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $from = $context->stringParameter('from', 'd/m/Y');
        $zone = DateValue::zone($context);

        if (!is_string($value)) {
            return $value;
        }

        return DateValue::parse(value: $value, format: $from, zone: $zone)?->format(DATE_ATOM) ?? $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'date.to_iso8601';
    }
}
