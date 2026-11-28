<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Date;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\DateValue;

// ── Rule: DateToTimestampRule ────────────────────────────────────────
// Reads a date as seconds since the Unix epoch.
// ─────────────────────────────────────────────────────────────────────

/**
 * Parses the string strictly in 'format' (Y-m-d unless set), in
 * 'timezone' (UTC unless set) when the text names no zone of its own,
 * and returns the Unix timestamp as an int. Strings that are not a real
 * date in the format are returned unchanged.
 *
 * Preconditions: 'format' uses DateTimeImmutable::createFromFormat() letters; 'timezone' is an identifier DateTimeZone accepts.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['published_at' => [['date_to_timestamp', ['format' => 'd/m/Y H:i', 'timezone' => 'America/Fortaleza']]]]
 *
 * References: ADR-008; SPEC-002 §4.
 */
final readonly class DateToTimestampRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $format = $context->stringParameter('format', 'Y-m-d');
        $zone = DateValue::zone($context);

        if (!is_string($value)) {
            return $value;
        }

        return DateValue::parse(value: $value, format: $format, zone: $zone)?->getTimestamp() ?? $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'date.to_timestamp';
    }
}
