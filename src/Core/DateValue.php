<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Core: DateValue ──────────────────────────────────────────────────
// Parses dates strictly, and reads the time zone and the clock of a rule.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads a date written in a DateTime format, strictly: fields the format
 * does not name are zero instead of the current time, a date PHP would
 * otherwise move (31/02, hour 25) or one followed by other characters
 * is not a date, and the date is read in the given time zone unless it
 * names its own. It also reads the 'timezone' parameter (UTC unless set)
 * and the 'now' parameter (the current time unless a DateTimeInterface
 * is given, which keeps rules that depend on today testable).
 *
 * Preconditions: the format uses DateTimeImmutable::createFromFormat()
 * letters.
 *
 * Thread-safety: stateless; parse() reads the last parse errors right
 * after its own call.
 *
 * Side effects: now() reads the system clock when 'now' is absent.
 *
 * Example:
 *
 *     DateValue::parse('31/02/2026', 'd/m/Y', new DateTimeZone('UTC')); // null
 *
 * References: PHP manual, DateTimeImmutable::createFromFormat(); ADR-008; SPEC-002 §4.
 */
final class DateValue
{
    public static function parse(string $value, string $format, DateTimeZone $zone): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(format: '!' . $format, datetime: $value, timezone: $zone);

        return $date !== false && DateTimeImmutable::getLastErrors() === false ? $date : null;
    }

    public static function zone(TransformationContext $context): DateTimeZone
    {
        $timezone = $context->stringParameter('timezone', 'UTC');

        if ($timezone === '') {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'timezone', expected: 'a time zone identifier');
        }

        try {
            return new DateTimeZone($timezone);
        } catch (Exception) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'timezone', expected: 'a time zone identifier', given: var_export($timezone, true));
        }
    }

    public static function now(TransformationContext $context, DateTimeZone $zone): DateTimeImmutable
    {
        /** @var mixed $now */
        $now = $context->parameter('now');

        if ($now === null && !$context->hasParameter('now')) {
            return new DateTimeImmutable('now', $zone);
        }

        return $now instanceof DateTimeInterface
            ? DateTimeImmutable::createFromInterface($now)
            : throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'now', expected: 'a DateTimeInterface', given: get_debug_type($now));
    }
}
