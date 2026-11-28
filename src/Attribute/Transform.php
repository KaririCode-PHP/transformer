<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Attribute;

use Attribute;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Attribute: Transform ─────────────────────────────────────────────
// Declares the transformation rules of a property.
// ─────────────────────────────────────────────────────────────────────

/**
 * Lists, on a property, the rule definitions AttributeTransformer applies
 * to its value in order, in the same forms the engine accepts: an alias,
 * a rule instance, or [alias or rule, parameters]. Repeatable; the rules
 * of every #[Transform] on a property run in order.
 *
 * Preconditions: rules are positional; named arguments would be lost
 * between the attribute and the engine, so they throw
 * InvalidRuleException.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     #[Transform('cpf_to_digits')]
 *     public string $cpf;
 *
 *     #[Transform(['mask', ['keep_start' => 0, 'keep_end' => 4]])]
 *     public string $card;
 *
 * References: ADR-003; SPEC-001 §7.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final readonly class Transform
{
    /** @var list<string|TransformationRule|array<array-key, mixed>> */
    public array $rules;

    /**
     * @param string|TransformationRule|array<array-key, mixed> ...$rules
     */
    public function __construct(string|TransformationRule|array ...$rules)
    {
        $named = [];
        $list = [];

        foreach ($rules as $key => $rule) {
            if (is_string($key)) {
                $named[] = $key;
            } else {
                $list[] = $rule;
            }
        }

        if ($named !== []) {
            throw InvalidRuleException::namedArguments(attribute: 'Transform', names: $named);
        }

        $this->rules = $list;
    }
}
