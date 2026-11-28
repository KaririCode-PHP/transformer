<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Configuration;

// ── Configuration: TransformerConfiguration ──────────────────────────
// Whether the engine records the changes the rules make.
// ─────────────────────────────────────────────────────────────────────

/**
 * Turns the record of changes on (the default) or off. Without it, the
 * result still holds the input and the transformed copy; only the list of
 * FieldTransformation entries stays empty, which saves memory on large
 * inputs.
 *
 * Preconditions: none.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     new TransformerEngine(configuration: new TransformerConfiguration(trackTransformations: false));
 *
 * References: ADR-002; SPEC-001 §8.
 */
final readonly class TransformerConfiguration
{
    public function __construct(
        public bool $trackTransformations = true,
    ) {}
}
