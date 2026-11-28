<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Contract;

// ── Contract: TransformationContext ──────────────────────────────────
// What a rule may read besides the value: the field, the data, its parameters.
// ─────────────────────────────────────────────────────────────────────

/**
 * Gives a rule the name of the field it transforms, every field of the input,
 * and the parameters of this use of the rule, with readers that check a
 * parameter's type. A parameter that is missing or of the wrong type is
 * the application's mistake: the typed readers throw
 * InvalidRuleException instead of letting a rule cast it.
 *
 * Preconditions: field paths use dots for nesting ("address.city"); a
 * key that contains dots itself is matched first.
 *
 * Thread-safety: immutable; the with methods return copies.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $times = $context->intParameter('times', 2);
 *     $header = $context->boolParameter('header', true);
 *
 * References: ADR-001; SPEC-001 §4.
 */
interface TransformationContext
{
    public string $field { get; }

    /** @var array<array-key, mixed> */
    public array $data { get; }

    /** @var array<string, mixed> */
    public array $parameters { get; }

    public function withField(string $field): static;

    /**
     * @param array<string, mixed> $parameters
     */
    public function withParameters(array $parameters): static;

    public function hasParameter(string $name): bool;

    public function parameter(string $name, mixed $default = null): mixed;

    public function intParameter(string $name, ?int $default = null): int;

    public function numberParameter(string $name, int|float|null $default = null): int|float;

    public function stringParameter(string $name, ?string $default = null): string;

    public function boolParameter(string $name, ?bool $default = null): bool;

    /**
     * @return array<array-key, mixed>
     */
    public function arrayParameter(string $name): array;

    public function hasField(string $path): bool;

    public function fieldValue(string $path): mixed;
}
