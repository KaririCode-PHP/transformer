<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Integration;

use KaririCode\Contract\Processor\Processor;
use KaririCode\Contract\Transformer\Transformer;
use KaririCode\Transformer\Core\TransformerEngine;
use KaririCode\Transformer\Exception\UnsupportedInputException;

// ── Integration: TransformationProcessor ─────────────────────────────
// A transformation step for kariricode/processor-pipeline and other contract consumers.
// ─────────────────────────────────────────────────────────────────────

/**
 * Transforms an array of fields against a fixed rule map behind the
 * kariricode/contract ports: as a Processor and as a Transformer it returns
 * the transformed copy, so a pipeline can convert data between its steps.
 *
 * Preconditions: the input is an array of fields; anything else throws
 * UnsupportedInputException.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none beyond the engine's observer.
 *
 * Example:
 *
 *     new Pipeline([new TransformationProcessor($engine, ['cpf' => ['cpf_to_digits']]), $next]);
 *
 * References: kariricode/contract Processor and Transformer; ADR-006.
 */
final readonly class TransformationProcessor implements Processor, Transformer
{
    /**
     * @param array<array-key, mixed> $fieldRules field path => list of rule definitions
     */
    public function __construct(
        private TransformerEngine $engine,
        private array $fieldRules,
    ) {}

    #[\Override]
    public function process(mixed $input): mixed
    {
        return $this->transform($input);
    }

    /**
     * @return array<array-key, mixed>
     */
    #[\Override]
    public function transform(mixed $object): array
    {
        if (!is_array($object)) {
            throw UnsupportedInputException::notAnArray(get_debug_type($object));
        }

        return $this->engine->transform($object, $this->fieldRules)->transformed;
    }
}
