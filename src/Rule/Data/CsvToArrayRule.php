<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Data;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\CsvReader;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Rule: CsvToArrayRule ─────────────────────────────────────────────
// Reads CSV text as a list of rows.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads the string as RFC 4180 CSV — quoted fields may hold separators,
 * line breaks and doubled enclosures — with 'separator' ("," unless
 * set) and 'enclosure' ('"' unless set). With 'header' (true unless
 * set) the first record names the fields and every other record becomes
 * a map from those names; the names must be unique and every record
 * must have as many fields, or the string is returned unchanged rather
 * than padded or cut. Without a header the records come back as lists.
 * Text that is not CSV (an enclosure left open) is returned unchanged.
 *
 * Preconditions: 'separator' and 'enclosure' are different single-byte characters, neither a line break; 'header' is a boolean.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['import' => [['csv_to_array', ['separator' => ';']]]]
 *
 * References: RFC 4180; ADR-008; SPEC-002 §2.
 */
final readonly class CsvToArrayRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $separator = self::character(context: $context, name: 'separator', default: ',');
        $enclosure = self::character(context: $context, name: 'enclosure', default: '"');
        $header = $context->boolParameter('header', true);

        if ($separator === $enclosure) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'enclosure', expected: 'a character other than the separator');
        }

        $records = is_string($value) ? CsvReader::records(csv: $value, separator: $separator, enclosure: $enclosure) : null;

        if ($records === null) {
            return $value;
        }

        return $header ? self::named($records) ?? $value : $records;
    }

    #[\Override]
    public function name(): string
    {
        return 'data.csv_to_array';
    }

    private static function character(TransformationContext $context, string $name, string $default): string
    {
        $character = $context->stringParameter($name, $default);

        return strlen($character) === 1 && $character !== "\r" && $character !== "\n"
            ? $character
            : throw InvalidRuleException::invalidParameter(field: $context->field, parameter: $name, expected: 'one single-byte character other than a line break', given: var_export($character, true));
    }

    /**
     * @param list<list<string>> $records
     *
     * @return list<array<string, string>>|null null when the records do not fit the header
     */
    private static function named(array $records): ?array
    {
        $names = array_shift($records) ?? [];
        $rows = [];

        if (count(array_unique($names)) !== count($names)) {
            return null;
        }

        foreach ($records as $record) {
            if (count($record) !== count($names)) {
                return null;
            }

            $rows[] = array_combine($names, $record);
        }

        return $rows;
    }
}
