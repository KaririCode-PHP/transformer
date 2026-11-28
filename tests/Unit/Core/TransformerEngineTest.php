<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Core;

use ArrayObject;
use KaririCode\Transformer\Configuration\TransformerConfiguration;
use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\InMemoryRuleRegistry;
use KaririCode\Transformer\Core\TransformerEngine;
use KaririCode\Transformer\Event\TransformationCompletedEvent;
use KaririCode\Transformer\Event\TransformationStartedEvent;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use KaririCode\Transformer\Result\FieldTransformation;
use KaririCode\Transformer\Rule\String\MaskRule;
use KaririCode\Transformer\Rule\String\PascalCaseRule;
use KaririCode\Transformer\Rule\String\SnakeCaseRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Unit: TransformerEngineTest ──────────────────────────────────────
// Rule maps, definitions, chaining, absent and nested fields, events.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(TransformerEngine::class)]
#[CoversClass(InMemoryRuleRegistry::class)]
#[CoversClass(TransformationStartedEvent::class)]
#[CoversClass(TransformationCompletedEvent::class)]
#[CoversClass(TransformerConfiguration::class)]
final class TransformerEngineTest extends TestCase
{
    #[Test]
    public function rulesRunInOrderEachOnTheOutputOfThePrevious(): void
    {
        $result = (new TransformerEngine())->transform(
            ['column' => 'First Name', 'cpf' => '123.456.789-09', 'price' => '1234.5'],
            ['column' => ['snake_case', 'kebab_case'], 'cpf' => ['cpf_to_digits'], 'price' => ['currency_format']],
        );

        self::assertSame(['column' => 'first-name', 'cpf' => '12345678909', 'price' => '1,234.50'], $result->transformed);
        self::assertSame(
            [
                ['column', 'string.snake_case', 'First Name', 'first_name'],
                ['column', 'string.kebab_case', 'first_name', 'first-name'],
                ['cpf', 'brazilian.cpf_to_digits', '123.456.789-09', '12345678909'],
                ['price', 'numeric.currency_format', '1234.5', '1,234.50'],
            ],
            array_map(static fn(FieldTransformation $change): array => [$change->field, $change->ruleName, $change->before, $change->after], $result->transformations),
        );
    }

    #[Test]
    public function definitionsMayBeAliasesRulesOrPairs(): void
    {
        $result = (new TransformerEngine())->transform(
            ['a' => 'Hello World', 'b' => 'Hello World', 'c' => 'ab', 'd' => 'hello world', 'e' => 'Hello World', 'f' => 'secret'],
            [
                'a' => ['snake_case'],
                'b' => [new SnakeCaseRule()],
                'c' => [['repeat', ['times' => 2]]],
                'd' => [[new PascalCaseRule()]],
                'e' => [['kebab_case']],
                'f' => [[new MaskRule(), ['keep_start' => 1, 'keep_end' => 1]]],
            ],
        );

        self::assertSame(['a' => 'hello_world', 'b' => 'hello_world', 'c' => 'abab', 'd' => 'HelloWorld', 'e' => 'hello-world', 'f' => 's****t'], $result->transformed);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidDefinitions(): iterable
    {
        yield 'rules not a list' => ['snake_case', 'the rules of a field are a list'];
        yield 'rules as a map' => [['first' => 'snake_case'], 'the rules of a field are a list'];
        yield 'a number' => [[42], 'a rule is an alias, a TransformationRule, or [alias or rule, parameters]'];
        yield 'an empty pair' => [[[]], 'the first item names the rule, by alias or as a TransformationRule'];
        yield 'three items' => [[['repeat', ['times' => 2], 'extra']], 'a rule is an alias, a TransformationRule, or [alias or rule, parameters]'];
        yield 'a map' => [[['rule' => 'snake_case']], 'a rule is an alias, a TransformationRule, or [alias or rule, parameters]'];
        yield 'a rule that is a number' => [[[42, []]], 'the first item names the rule, by alias or as a TransformationRule'];
        yield 'parameters not an array' => [[['repeat', 5]], 'the parameters of a rule are a map of names to values'];
        yield 'parameters as a list' => [[['repeat', [5, '-']]], 'the parameters of a rule are a map of names to values'];
        yield 'parameters partly a list' => [[['repeat', ['times' => 2, '-']]], 'the parameters of a rule are a map of names to values'];
    }

    #[Test]
    #[DataProvider('invalidDefinitions')]
    public function malformedDefinitionsAreRefused(mixed $rules, string $reason): void
    {
        try {
            (new TransformerEngine())->transform(['name' => 'x'], ['name' => $rules]);
            self::fail('A malformed definition was accepted.');
        } catch (InvalidRuleException $exception) {
            self::assertSame(TransformerCode::InvalidRuleDefinition, $exception->errorCode);
            self::assertSame('[TRF_3403] Invalid rule definition for field "name": ' . $reason . '.', $exception->getMessage());
        }
    }

    #[Test]
    public function definitionsAreCheckedEvenForFieldsTheDataLacks(): void
    {
        $engine = new TransformerEngine();

        try {
            $engine->transform([], ['nick' => ['snake_cse']]);
            self::fail('An unknown alias was accepted.');
        } catch (InvalidRuleException $exception) {
            self::assertSame('[TRF_3402] No rule is registered under the alias "snake_cse".', $exception->getMessage());
        }

        $this->expectExceptionObject(InvalidRuleException::invalidDefinition(field: 'nick', reason: 'the rules of a field are a list'));
        $engine->transform([], ['nick' => 'snake_case']);
    }

    #[Test]
    public function fieldsTheDataLacksAreNotAdded(): void
    {
        $result = (new TransformerEngine())->transform(
            ['name' => 'First Name', 'nick' => null],
            ['name' => ['snake_case'], 'email' => ['snake_case', 'kebab_case'], 'address.city' => ['snake_case'], 'nick' => ['snake_case', 'json_encode']],
        );

        self::assertSame(['name' => 'first_name', 'nick' => 'null'], $result->transformed);
        self::assertSame(['name', 'nick'], $result->transformedFields());
    }

    #[Test]
    public function nestedFieldsAreWrittenWhereTheyWereRead(): void
    {
        $data = ['user' => ['cpf' => '123.456.789-09', 'name' => 'x'], 'tags' => ['a b', 'c d'], 'a.b' => 'Literal Key', 'a' => ['b' => 'Nested Key'], 7 => 'Seven Up'];

        $result = (new TransformerEngine())->transform($data, ['user.cpf' => ['cpf_to_digits'], 'tags.1' => ['snake_case'], 'a.b' => ['snake_case'], 7 => ['kebab_case']]);

        self::assertSame(['user' => ['cpf' => '12345678909', 'name' => 'x'], 'tags' => ['a b', 'c_d'], 'a.b' => 'literal_key', 'a' => ['b' => 'Nested Key'], 7 => 'seven-up'], $result->transformed);
        self::assertSame($data, $result->original);
        self::assertSame('12345678909', $result->get('user.cpf'));
    }

    #[Test]
    public function rulesSeeTheFieldTheirParametersAndTheDataAsGiven(): void
    {
        /** @var ArrayObject<int, array{mixed, string, array<string, mixed>, array<array-key, mixed>}> $seen */
        $seen = new ArrayObject();
        $spy = new readonly class ($seen) implements TransformationRule {
            /** @param ArrayObject<int, array{mixed, string, array<string, mixed>, array<array-key, mixed>}> $seen */
            public function __construct(private ArrayObject $seen) {}

            public function transform(mixed $value, TransformationContext $context): mixed
            {
                $this->seen->append([$value, $context->field, $context->parameters, $context->data]);

                return is_string($value) ? strtoupper($value) : $value;
            }

            public function name(): string
            {
                return 'test.spy';
            }
        };
        $data = ['address' => ['city' => 'crato'], 'state' => 'ce'];

        (new TransformerEngine())->transform($data, ['address.city' => [[$spy, ['mode' => 1, 'strict' => false]]], 'state' => [$spy, [$spy, ['again' => true]]]]);

        self::assertSame([
            ['crato', 'address.city', ['mode' => 1, 'strict' => false], $data],
            ['ce', 'state', [], $data],
            ['CE', 'state', ['again' => true], $data],
        ], $seen->getArrayCopy());
    }

    #[Test]
    public function aMisconfiguredRuleNamesTheField(): void
    {
        $this->expectExceptionObject(InvalidRuleException::missingParameter(field: 'items', parameter: 'field'));

        (new TransformerEngine())->transform(['items' => [['id' => 1]]], ['items' => ['pluck']]);
    }

    #[Test]
    public function trackingCanBeTurnedOff(): void
    {
        $configuration = new TransformerConfiguration(trackTransformations: false);
        $engine = new TransformerEngine(configuration: $configuration);
        $result = $engine->transform(['a' => 'Hello World'], ['a' => ['snake_case', 'kebab_case']]);

        self::assertSame($configuration, $engine->configuration);
        self::assertSame(['a' => 'hello-world'], $result->transformed);
        self::assertSame([], $result->transformations);
        self::assertTrue($result->wasTransformed());
        self::assertTrue($result->isFieldTransformed('a'));
        self::assertTrue((new TransformerConfiguration())->trackTransformations);
    }

    #[Test]
    public function rulesThatChangeNothingRecordNothing(): void
    {
        $result = (new TransformerEngine())->transform(['a' => 'hello', 'b' => 1], ['a' => ['snake_case', 'kebab_case'], 'b' => ['cpf_to_digits']]);

        self::assertSame([], $result->transformations);
        self::assertFalse($result->wasTransformed());
    }

    #[Test]
    public function theEngineUsesTheRegistryItIsGiven(): void
    {
        $registry = new InMemoryRuleRegistry();
        $registry->register('class_name', new PascalCaseRule());
        $engine = new TransformerEngine($registry);

        self::assertSame($registry, $engine->registry);
        self::assertSame(['a' => 'XY'], $engine->transform(['a' => 'x y'], ['a' => ['class_name']])->transformed);
        $this->expectExceptionObject(InvalidRuleException::unknownAlias('snake_case'));
        $engine->transform(['a' => 'x'], ['a' => ['snake_case']]);
    }

    #[Test]
    public function theObserverSeesTheStartAndTheEnd(): void
    {
        /** @var ArrayObject<int, object> $events */
        $events = new ArrayObject();
        $engine = new TransformerEngine(observer: static function (object $event) use ($events): void {
            $events->append($event);
        });

        $result = $engine->transform(['a' => 'A B'], ['a' => ['snake_case'], 3 => ['snake_case']]);

        self::assertCount(2, $events);
        $started = $events[0];
        $completed = $events[1];
        self::assertInstanceOf(TransformationStartedEvent::class, $started);
        self::assertInstanceOf(TransformationCompletedEvent::class, $completed);
        self::assertSame(['a', '3'], $started->fields);
        self::assertEqualsWithDelta(microtime(true), $started->timestamp, 5.0);
        self::assertSame($result, $completed->result);
        self::assertGreaterThan(0.0, $completed->durationMs);
        self::assertLessThan(5000.0, $completed->durationMs);
    }

    #[Test]
    public function theRegistryKeepsRulesByAlias(): void
    {
        $registry = new InMemoryRuleRegistry();
        $rule = new SnakeCaseRule();
        $registry->register('underscored', $rule);

        self::assertSame($rule, $registry->resolve('underscored'));
        self::assertTrue($registry->has('underscored'));
        self::assertFalse($registry->has('snake_case'));
        self::assertSame(['underscored'], $registry->aliases());

        foreach ([
            [static fn() => $registry->register('underscored', $rule), TransformerCode::DuplicateRuleAlias, 'A rule is already registered under the alias "underscored".'],
            [static fn() => $registry->register('', $rule), TransformerCode::InvalidRuleDefinition, 'A rule alias must not be empty.'],
            [static fn() => $registry->resolve('snake_case'), TransformerCode::UnknownRuleAlias, 'No rule is registered under the alias "snake_case".'],
        ] as [$call, $code, $message]) {
            try {
                $call();
                self::fail($message);
            } catch (InvalidRuleException $exception) {
                self::assertSame($code, $exception->errorCode);
                self::assertStringEndsWith($message, $exception->getMessage());
            }
        }
    }

    #[Test]
    public function theDefaultRegistryHoldsTheThirtyTwoRules(): void
    {
        $registry = (new TransformerEngine())->registry;
        $aliases = $registry->aliases();

        self::assertCount(32, $aliases);
        self::assertSame(['camel_case', 'pascal_case', 'snake_case', 'kebab_case', 'mask'], array_slice($aliases, 0, 5));
        self::assertSame(['cnpj_to_digits', 'cep_to_digits', 'phone_format'], array_slice($aliases, -3));
        self::assertSame('date.to_iso8601', $registry->resolve('date_to_iso8601')->name());
        self::assertSame('date.relative', $registry->resolve('relative_date')->name());
        self::assertCount(count($aliases), array_unique(array_map(static fn(string $alias): string => $registry->resolve($alias)->name(), $aliases)));
    }
}
