<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Result;

// ── Result: TransformedObject ────────────────────────────────────────
// A transformed copy of an object, with the result that produced it.
// ─────────────────────────────────────────────────────────────────────

/**
 * Returns what AttributeTransformer produced: the object with its
 * #[Transform] properties transformed (a copy when anything changed, the same
 * instance otherwise) and the TransformationResult of its property values.
 *
 * Preconditions: built by AttributeTransformer.
 *
 * Thread-safety: immutable; the object is whatever the change applier
 * returned.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $transformed = $transformer->transform($signUp);
 *     $signUp = $transformed->object;
 *     $transformed->result->transformedFields(); // ['cpf', 'phone']
 *
 * References: ADR-003.
 */
final readonly class TransformedObject
{
    public function __construct(
        public object $object,
        public TransformationResult $result,
    ) {}
}
