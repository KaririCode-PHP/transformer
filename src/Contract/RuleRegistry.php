<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Contract;

// ── Contract: RuleRegistry ───────────────────────────────────────────
// Rules by alias, so definitions can name them.
// ─────────────────────────────────────────────────────────────────────

/**
 * Maps the aliases rule definitions use ('snake_case', 'json_decode') to rule
 * instances. Registration is part of setting the engine up; resolving
 * happens on every transformation.
 *
 * Preconditions: an alias is registered once.
 *
 * Thread-safety: implementations decide; register before sharing.
 *
 * Side effects: register() changes the registry.
 *
 * Example:
 *
 *     $registry->register('collapse_dashes', new CollapseDashesRule());
 *     $rule = $registry->resolve('collapse_dashes');
 *
 * References: ADR-001; SPEC-002.
 */
interface RuleRegistry
{
    public function register(string $alias, TransformationRule $rule): void;

    public function resolve(string $alias): TransformationRule;

    public function has(string $alias): bool;

    /**
     * @return list<string>
     */
    public function aliases(): array;
}
