<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

use Closure;
use KaririCode\Transformer\Configuration\TransformerConfiguration;
use KaririCode\Transformer\Contract\RuleRegistry;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Event\TransformationCompletedEvent;
use KaririCode\Transformer\Event\TransformationStartedEvent;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Result\FieldTransformation;
use KaririCode\Transformer\Result\TransformationResult;

// ── Core: TransformerEngine ──────────────────────────────────────────
// Runs the rules of each field in order and keeps a record of what changed.
// ─────────────────────────────────────────────────────────────────────

/**
 * Transforms an array of fields against a rule map: for each field present
 * in the input, it reads the value by its dotted path, passes it through
 * the field's rules in order (each rule receives the previous one's
 * output) and writes the result back at the same path of a copy. Fields
 * the input does not have are left out, so transforming adds no keys. With
 * tracking on, every change a rule made is recorded. The observer
 * receives a TransformationStartedEvent and a TransformationCompletedEvent
 * per call.
 *
 * Preconditions: a rule definition is an alias, a TransformationRule, or a
 * list [alias or rule, parameters]; anything else is an
 * InvalidRuleException, as is an unknown alias or a missing parameter.
 *
 * Thread-safety: immutable; the registry is read, never written.
 *
 * Side effects: the observer is called twice per transformation.
 *
 * Example:
 *
 *     $result = (new TransformerEngine())->transform(
 *         ['column' => 'firstName', 'cpf' => '123.456.789-09'],
 *         ['column' => ['snake_case'], 'cpf' => ['cpf_to_digits']],
 *     );
 *     $result->transformed; // ['column' => 'first_name', 'cpf' => '12345678909']
 *
 * References: ADR-001; ADR-002; ADR-006; SPEC-001.
 */
final readonly class TransformerEngine
{
    public RuleRegistry $registry;

    public TransformerConfiguration $configuration;

    private ?Closure $observer;

    public function __construct(
        ?RuleRegistry $registry = null,
        ?TransformerConfiguration $configuration = null,
        ?Closure $observer = null,
    ) {
        $this->registry = $registry ?? InMemoryRuleRegistry::withDefaults();
        $this->configuration = $configuration ?? new TransformerConfiguration();
        $this->observer = $observer;
    }

    /**
     * @param array<array-key, mixed> $data       the fields to transform
     * @param array<array-key, mixed> $fieldRules field path => list of rule definitions
     */
    public function transform(array $data, array $fieldRules): TransformationResult
    {
        $startedAt = hrtime(true);
        $this->emit(new TransformationStartedEvent(fields: array_map(strval(...), array_keys($fieldRules)), timestamp: microtime(true)));
        $context = new RuleContext(data: $data);
        $transformed = $data;
        $transformations = [];

        /** @var mixed $rules */
        foreach ($fieldRules as $field => $rules) {
            $field = (string) $field;
            $definitions = $this->definitions($field, $rules);

            if (!DataPath::exists($data, $field)) {
                continue;
            }

            $changed = $this->transformField($context->withField($field), $definitions);
            $transformed = DataPath::with(data: $transformed, path: $field, value: $changed[0]);
            array_push($transformations, ...$changed[1]);
        }

        $result = new TransformationResult(original: $data, transformed: $transformed, transformations: $transformations);
        $this->emit(new TransformationCompletedEvent(result: $result, durationMs: (hrtime(true) - $startedAt) / 1_000_000));

        return $result;
    }

    /**
     * @param list<array{TransformationRule, array<string, mixed>}> $definitions
     *
     * @return array{mixed, list<FieldTransformation>}
     */
    private function transformField(RuleContext $context, array $definitions): array
    {
        /** @var mixed $value */
        $value = $context->fieldValue($context->field);
        $changes = [];

        foreach ($definitions as [$rule, $parameters]) {
            /** @var mixed $before */
            $before = $value;
            /** @var mixed $value */
            $value = $rule->transform($value, $context->withParameters($parameters));

            if ($this->configuration->trackTransformations && $before !== $value) {
                $changes[] = new FieldTransformation(field: $context->field, ruleName: $rule->name(), before: $before, after: $value);
            }
        }

        return [$value, $changes];
    }

    /**
     * @return list<array{TransformationRule, array<string, mixed>}>
     */
    private function definitions(string $field, mixed $rules): array
    {
        if (!is_array($rules) || !array_is_list($rules)) {
            throw InvalidRuleException::invalidDefinition(field: $field, reason: 'the rules of a field are a list');
        }

        return array_map(fn(mixed $definition): array => $this->definition($field, $definition), $rules);
    }

    /**
     * @return array{TransformationRule, array<string, mixed>}
     */
    private function definition(string $field, mixed $definition): array
    {
        if ($definition instanceof TransformationRule) {
            return [$definition, []];
        }

        if (is_string($definition)) {
            return [$this->registry->resolve($definition), []];
        }

        if (!is_array($definition) || !array_is_list($definition) || count($definition) > 2) {
            throw InvalidRuleException::invalidDefinition(field: $field, reason: 'a rule is an alias, a TransformationRule, or [alias or rule, parameters]');
        }

        return [$this->rule($field, $definition[0] ?? null), $this->parameters($field, $definition[1] ?? [])];
    }

    private function rule(string $field, mixed $rule): TransformationRule
    {
        if (is_string($rule)) {
            return $this->registry->resolve($rule);
        }

        return $rule instanceof TransformationRule ? $rule : throw InvalidRuleException::invalidDefinition(field: $field, reason: 'the first item names the rule, by alias or as a TransformationRule');
    }

    /**
     * @return array<string, mixed>
     */
    private function parameters(string $field, mixed $parameters): array
    {
        if (!is_array($parameters) || array_filter(array_keys($parameters), is_int(...)) !== []) {
            throw InvalidRuleException::invalidDefinition(field: $field, reason: 'the parameters of a rule are a map of names to values');
        }

        /** @var array<string, mixed> $parameters */
        return $parameters;
    }

    private function emit(TransformationStartedEvent|TransformationCompletedEvent $event): void
    {
        if ($this->observer !== null) {
            ($this->observer)($event);
        }
    }
}
