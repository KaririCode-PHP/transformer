<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Date;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\DateValue;

// ── Rule: RelativeDateRule ───────────────────────────────────────────
// Describes a date relative to now, in English.
// ─────────────────────────────────────────────────────────────────────

/**
 * Parses the string strictly in 'from' (Y-m-d H:i:s unless set) in
 * 'timezone' (UTC unless set) and describes its distance from 'now'
 * (the current time unless a DateTimeInterface is given): "just now"
 * under a minute, then "3 minutes ago", "1 hour ago", "2 days from
 * now", counting a month as 30 days and a year as 365. Strings that
 * are not a real date are returned unchanged.
 *
 * Preconditions: 'from' uses DateTimeImmutable::createFromFormat() letters; 'timezone' is an identifier DateTimeZone accepts; 'now', when given, is a DateTimeInterface.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['commented_at' => ['relative_date']]
 *
 * References: ADR-008; SPEC-002 §4.
 */
final readonly class RelativeDateRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $from = $context->stringParameter('from', 'Y-m-d H:i:s');
        $zone = DateValue::zone($context);
        $now = DateValue::now($context, $zone);
        $date = is_string($value) ? DateValue::parse(value: $value, format: $from, zone: $zone) : null;

        if ($date === null) {
            return $value;
        }

        return self::phrase(abs($now->getTimestamp() - $date->getTimestamp()), $date->diff($now)->invert === 1 ? 'from now' : 'ago');
    }

    #[\Override]
    public function name(): string
    {
        return 'date.relative';
    }

    /** @var list<array{int, string}> unit lengths in seconds, longest first */
    private const array UNITS = [[31_536_000, 'year'], [2_592_000, 'month'], [86_400, 'day'], [3_600, 'hour'], [60, 'minute']];

    private static function phrase(int $seconds, string $direction): string
    {
        foreach (self::UNITS as [$length, $unit]) {
            if ($seconds >= $length) {
                $count = intdiv($seconds, $length);

                return $count . ' ' . $unit . ($count === 1 ? '' : 's') . ' ' . $direction;
            }
        }

        return 'just now';
    }
}
