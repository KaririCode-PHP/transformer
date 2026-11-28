<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

// ── Core: Words ──────────────────────────────────────────────────────
// Splits identifiers and phrases into words for the case rules.
// ─────────────────────────────────────────────────────────────────────

/**
 * Splits a string into words at every character that is not a letter,
 * a combining mark or a digit, and at the case changes of an identifier:
 * "helloWorld" is hello and World, "HTTPServer" is HTTP and Server,
 * "São Paulo" is São and Paulo. Digits stay with the word before them
 * ("user2Name" is user2 and Name). Invalid UTF-8 bytes become "?" first
 * and then separate words. capitalize() upper-cases the first letter of
 * a word with the Unicode title mapping and lower-cases the rest.
 *
 * Preconditions: none.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     Words::of('getHTTPResponse code'); // ['get', 'HTTP', 'Response', 'code']
 *
 * References: Unicode general categories; ADR-005; SPEC-002 §1.
 */
final class Words
{
    /**
     * @return list<string>
     */
    public static function of(string $value): array
    {
        $marked = preg_replace(pattern: '/(?<=[\p{Ll}\p{Mn}\p{Nd}])(?=\p{Lu})|(?<=\p{Lu})(?=\p{Lu}\p{Ll})/u', replacement: ' ', subject: mb_scrub($value, 'UTF-8')) ?? '';
        $words = preg_split(pattern: '/[^\p{L}\p{M}\p{N}]+/u', subject: $marked, flags: PREG_SPLIT_NO_EMPTY);

        return $words === false ? [] : $words;
    }

    public static function capitalize(string $word): string
    {
        $first = mb_substr(string: $word, start: 0, length: 1, encoding: 'UTF-8');

        return mb_convert_case(string: $first, mode: MB_CASE_TITLE, encoding: 'UTF-8') . mb_strtolower(mb_substr(string: $word, start: 1, encoding: 'UTF-8'), 'UTF-8');
    }
}
