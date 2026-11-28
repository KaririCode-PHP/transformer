<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Data;

use JsonException;
use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\IntegerParameter;

// ── Rule: JsonDecodeRule ─────────────────────────────────────────────
// Decodes a JSON string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Decodes JSON text, objects as arrays unless 'assoc' is false, with
 * the 'flags' given (JSON_BIGINT_AS_STRING unless set, so integers
 * beyond PHP's range keep every digit as strings instead of turning
 * into floats) and the 'depth' (512 unless set). Text that is not JSON
 * is returned unchanged; the JSON literal null decodes to null.
 *
 * Preconditions: 'assoc' is a boolean; 'flags' is an integer; 'depth' is an integer from 1 to 2147483647.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['settings' => ['json_decode']]
 *
 * References: RFC 8259; ADR-008; SPEC-002 §2.
 */
final readonly class JsonDecodeRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $associative = $context->boolParameter('assoc', true);
        $flags = $context->intParameter('flags', JSON_BIGINT_AS_STRING);
        $depth = IntegerParameter::depth($context);

        if (!is_string($value)) {
            return $value;
        }

        try {
            return json_decode(json: $value, associative: $associative, depth: $depth, flags: $flags | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $value;
        }
    }

    #[\Override]
    public function name(): string
    {
        return 'data.json_decode';
    }
}
