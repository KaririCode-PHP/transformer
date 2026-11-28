<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Encoding;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Rule: HashRule ───────────────────────────────────────────────────
// Writes the hex digest of a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Hashes the string with 'algo' (sha256 unless set; any algorithm
 * hash_algos() lists, in any letter case) and returns the lower-case
 * hex digest. For checksums and fingerprints: passwords need
 * password_hash(), and integrity against an adversary needs sha256 or
 * stronger, not md5 or sha1.
 *
 * Preconditions: 'algo' is an algorithm hash_algos() lists.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['checksum' => [['hash', ['algo' => 'sha256']]]]
 *
 * References: PHP hash_algos(); NIST FIPS 180-4; SPEC-002 §3.
 */
final readonly class HashRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $name = $context->stringParameter('algo', 'sha256');
        $algorithm = strtolower($name);

        if (!in_array(needle: $algorithm, haystack: hash_algos(), strict: true)) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'algo', expected: 'an algorithm hash_algos() lists', given: var_export($name, true));
        }

        return is_string($value) ? hash($algorithm, $value) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'encoding.hash';
    }
}
