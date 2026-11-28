<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Core;

use KaririCode\Transformer\Attribute\Transform;
use KaririCode\Transformer\Core\AttributeRules;
use KaririCode\Transformer\Core\AttributeTransformer;
use KaririCode\Transformer\Core\TransformerEngine;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use KaririCode\Transformer\Result\TransformedObject;
use KaririCode\Transformer\Rule\String\SnakeCaseRule;
use KaririCode\Transformer\Tests\Support\Customer;
use KaririCode\Transformer\Tests\Support\ReflectionApplier;
use KaririCode\Transformer\Tests\Support\ReflectionInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

// ── Unit: AttributeTransformerTest ───────────────────────────────────
// #[Transform] through the contract PropertyInspector and PropertyChangeApplier.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(AttributeTransformer::class)]
#[CoversClass(AttributeRules::class)]
#[CoversClass(Transform::class)]
#[CoversClass(TransformedObject::class)]
final class AttributeTransformerTest extends TestCase
{
    #[Test]
    public function aReadonlyObjectComesBackAsATransformedCopy(): void
    {
        $inspector = new ReflectionInspector();
        $applier = new ReflectionApplier();
        $customer = new Customer(
            cpf: '123.456.789-09',
            phone: '+55 (88) 99999-0000',
            card: '4111111111111111',
            preferences: '{"theme": {"mode": "dark"}, "lang": "pt-BR"}',
        );

        $transformed = (new AttributeTransformer(new TransformerEngine(), $inspector, $applier))->transform($customer);
        $copy = $transformed->object;

        self::assertInstanceOf(Customer::class, $copy);
        self::assertNotSame($customer, $copy);
        self::assertSame(
            ['12345678909', '(88) 99999-0000', '************1111', ['theme.mode' => 'dark', 'lang' => 'pt-BR'], '123.456.789-09'],
            [$copy->cpf, $copy->phone, $copy->card, $copy->preferences, $copy->notTransformed],
        );
        self::assertSame('123.456.789-09', $customer->cpf);
        self::assertSame(['cpf', 'phone', 'card', 'preferences'], $transformed->result->transformedFields());
        self::assertSame(['data.json_decode', 'structure.flatten'], array_map(static fn($change): string => $change->ruleName, $transformed->result->transformationsFor('preferences')));
        self::assertSame([['cpf' => '12345678909', 'phone' => '(88) 99999-0000', 'card' => '************1111', 'preferences' => ['theme.mode' => 'dark', 'lang' => 'pt-BR']]], $applier->applied);
        self::assertSame([Transform::class], $inspector->inspected);
    }

    #[Test]
    public function onlyThePropertiesThatChangedAreApplied(): void
    {
        $applier = new ReflectionApplier();
        $transformer = new AttributeTransformer(new TransformerEngine(), new ReflectionInspector(), $applier);

        $transformed = $transformer->transform(new Customer(cpf: '12345678909', phone: '8835210000'));

        self::assertSame([['phone' => '(88) 3521-0000', 'preferences' => []]], $applier->applied);
        self::assertSame(['cpf' => '12345678909', 'phone' => '(88) 3521-0000', 'card' => '', 'preferences' => []], $transformed->result->transformed);
    }

    #[Test]
    public function anObjectThatNeedsNothingIsReturnedAsItIs(): void
    {
        $applier = new ReflectionApplier();
        $customer = new Customer(cpf: '12345678909', phone: '(88) 99999-0000', preferences: []);

        $transformed = (new AttributeTransformer(new TransformerEngine(), new ReflectionInspector(), $applier))->transform($customer);

        self::assertSame($customer, $transformed->object);
        self::assertSame([], $applier->applied);
        self::assertFalse($transformed->result->wasTransformed());
    }

    #[Test]
    public function anObjectWithoutAttributesIsReturnedAsItIs(): void
    {
        $object = new stdClass();
        $object->name = 'First Name';

        $transformed = (new AttributeTransformer(new TransformerEngine(), new ReflectionInspector(), new ReflectionApplier()))->transform($object);

        self::assertSame($object, $transformed->object);
        self::assertSame([], $transformed->result->transformed);
    }

    #[Test]
    public function theCollectorIgnoresOtherAttributesAndJoinsRepeatedOnes(): void
    {
        $snake = new SnakeCaseRule();
        $rules = new AttributeRules();
        $rules->handleAttribute('a', new Transform('camel_case'), 'First Name');
        $rules->handleAttribute('a', new Transform($snake, ['repeat', ['times' => 2]]), 'ignored: the first value stands');
        $rules->handleAttribute('a', new stdClass(), 'ignored');
        $rules->handleAttribute('b', new stdClass(), 'not collected');
        $rules->handleAttribute('c', new Transform('kebab_case'), 'Y Z');

        self::assertSame(['a' => ['camel_case', $snake, ['repeat', ['times' => 2]]], 'c' => ['kebab_case']], $rules->fieldRules());
        self::assertSame(['a' => 'First Name', 'c' => 'Y Z'], $rules->values());
    }

    #[Test]
    public function transformTakesPositionalRulesOnly(): void
    {
        self::assertSame(['snake_case', ['repeat', ['times' => 3]]], (new Transform('snake_case', ['repeat', ['times' => 3]]))->rules);
        self::assertSame([], (new Transform())->rules);

        try {
            new Transform('snake_case', mode: 'strict', level: 'high');
            self::fail('Named arguments were accepted.');
        } catch (InvalidRuleException $exception) {
            self::assertSame(TransformerCode::NamedAttributeArguments, $exception->errorCode);
            self::assertSame("[TRF_3406] #[Transform] takes no named arguments (mode, level); pass parameters as ['alias', ['parameter' => value]].", $exception->getMessage());
        }
    }
}
