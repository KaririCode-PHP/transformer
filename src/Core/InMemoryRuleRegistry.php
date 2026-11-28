<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

use KaririCode\Transformer\Contract\RuleRegistry;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Rule\Brazilian\CepToDigitsRule;
use KaririCode\Transformer\Rule\Brazilian\CnpjToDigitsRule;
use KaririCode\Transformer\Rule\Brazilian\CpfToDigitsRule;
use KaririCode\Transformer\Rule\Brazilian\PhoneFormatRule;
use KaririCode\Transformer\Rule\Data\ArrayToKeyValueRule;
use KaririCode\Transformer\Rule\Data\CsvToArrayRule;
use KaririCode\Transformer\Rule\Data\ImplodeRule;
use KaririCode\Transformer\Rule\Data\JsonDecodeRule;
use KaririCode\Transformer\Rule\Data\JsonEncodeRule;
use KaririCode\Transformer\Rule\Date\AgeRule;
use KaririCode\Transformer\Rule\Date\DateToIso8601Rule;
use KaririCode\Transformer\Rule\Date\DateToTimestampRule;
use KaririCode\Transformer\Rule\Date\RelativeDateRule;
use KaririCode\Transformer\Rule\Encoding\Base64DecodeRule;
use KaririCode\Transformer\Rule\Encoding\Base64EncodeRule;
use KaririCode\Transformer\Rule\Encoding\HashRule;
use KaririCode\Transformer\Rule\Numeric\CurrencyFormatRule;
use KaririCode\Transformer\Rule\Numeric\NumberToWordsRule;
use KaririCode\Transformer\Rule\Numeric\OrdinalRule;
use KaririCode\Transformer\Rule\Numeric\PercentageRule;
use KaririCode\Transformer\Rule\String\CamelCaseRule;
use KaririCode\Transformer\Rule\String\KebabCaseRule;
use KaririCode\Transformer\Rule\String\MaskRule;
use KaririCode\Transformer\Rule\String\PascalCaseRule;
use KaririCode\Transformer\Rule\String\RepeatRule;
use KaririCode\Transformer\Rule\String\ReverseRule;
use KaririCode\Transformer\Rule\String\SnakeCaseRule;
use KaririCode\Transformer\Rule\Structure\FlattenRule;
use KaririCode\Transformer\Rule\Structure\GroupByRule;
use KaririCode\Transformer\Rule\Structure\PluckRule;
use KaririCode\Transformer\Rule\Structure\RenameKeysRule;
use KaririCode\Transformer\Rule\Structure\UnflattenRule;

// ── Core: InMemoryRuleRegistry ───────────────────────────────────────
// The RuleRegistry the engine uses unless given another.
// ─────────────────────────────────────────────────────────────────────

/**
 * Keeps rules by alias in memory. withDefaults() registers the 32
 * built-in rules; applications add their own under aliases of their
 * choice.
 *
 * Preconditions: an alias is a non-empty string, registered once.
 *
 * Thread-safety: register before sharing; resolution only reads.
 *
 * Side effects: register() adds to the registry.
 *
 * Example:
 *
 *     $registry = InMemoryRuleRegistry::withDefaults();
 *     $registry->register('collapse_dashes', new CollapseDashesRule());
 *
 * References: ADR-001; SPEC-002.
 */
final class InMemoryRuleRegistry implements RuleRegistry
{
    /** @var array<string, TransformationRule> */
    private array $rules = [];

    public static function withDefaults(): self
    {
        $registry = new self();

        foreach (self::defaults() as $alias => $rule) {
            $registry->register($alias, $rule);
        }

        return $registry;
    }

    #[\Override]
    public function register(string $alias, TransformationRule $rule): void
    {
        if ($alias === '') {
            throw InvalidRuleException::emptyAlias();
        }

        if (isset($this->rules[$alias])) {
            throw InvalidRuleException::duplicateAlias($alias);
        }

        $this->rules[$alias] = $rule;
    }

    #[\Override]
    public function resolve(string $alias): TransformationRule
    {
        return $this->rules[$alias] ?? throw InvalidRuleException::unknownAlias($alias);
    }

    #[\Override]
    public function has(string $alias): bool
    {
        return isset($this->rules[$alias]);
    }

    #[\Override]
    public function aliases(): array
    {
        return array_keys($this->rules);
    }

    /**
     * @return array<string, TransformationRule>
     */
    private static function defaults(): array
    {
        return [
            'camel_case' => new CamelCaseRule(),
            'pascal_case' => new PascalCaseRule(),
            'snake_case' => new SnakeCaseRule(),
            'kebab_case' => new KebabCaseRule(),
            'mask' => new MaskRule(),
            'reverse' => new ReverseRule(),
            'repeat' => new RepeatRule(),
            'json_encode' => new JsonEncodeRule(),
            'json_decode' => new JsonDecodeRule(),
            'csv_to_array' => new CsvToArrayRule(),
            'array_to_key_value' => new ArrayToKeyValueRule(),
            'implode' => new ImplodeRule(),
            'base64_encode' => new Base64EncodeRule(),
            'base64_decode' => new Base64DecodeRule(),
            'hash' => new HashRule(),
            'date_to_timestamp' => new DateToTimestampRule(),
            'date_to_iso8601' => new DateToIso8601Rule(),
            'age' => new AgeRule(),
            'relative_date' => new RelativeDateRule(),
            'currency_format' => new CurrencyFormatRule(),
            'percentage' => new PercentageRule(),
            'ordinal' => new OrdinalRule(),
            'number_to_words' => new NumberToWordsRule(),
            'flatten' => new FlattenRule(),
            'unflatten' => new UnflattenRule(),
            'pluck' => new PluckRule(),
            'group_by' => new GroupByRule(),
            'rename_keys' => new RenameKeysRule(),
            'cpf_to_digits' => new CpfToDigitsRule(),
            'cnpj_to_digits' => new CnpjToDigitsRule(),
            'cep_to_digits' => new CepToDigitsRule(),
            'phone_format' => new PhoneFormatRule(),
        ];
    }
}
