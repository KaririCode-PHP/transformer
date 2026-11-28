<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Integration;

use ArrayObject;
use KaririCode\Contract\Inspection\PropertyChangeApplier;
use KaririCode\Contract\Inspection\PropertyInspector;
use KaririCode\Transformer\Configuration\TransformerConfiguration;
use KaririCode\Transformer\Contract\RuleRegistry;
use KaririCode\Transformer\Core\AttributeTransformer;
use KaririCode\Transformer\Core\TransformerEngine;
use KaririCode\Transformer\Exception\ConfigurationException;
use KaririCode\Transformer\Exception\TransformerCode;
use KaririCode\Transformer\Exception\UnsupportedInputException;
use KaririCode\Transformer\Integration\TransformationProcessor;
use KaririCode\Transformer\Provider\TransformerServiceProvider;
use KaririCode\Transformer\Tests\Support\Customer;
use KaririCode\Transformer\Tests\Support\MemoryContainer;
use KaririCode\Transformer\Tests\Support\ReflectionApplier;
use KaririCode\Transformer\Tests\Support\ReflectionInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

// ── Unit: IntegrationTest ────────────────────────────────────────────
// The contract Processor and Transformer, and the service provider.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(TransformationProcessor::class)]
#[CoversClass(TransformerServiceProvider::class)]
final class IntegrationTest extends TestCase
{
    #[Test]
    public function theProcessorReturnsTheTransformedFields(): void
    {
        $processor = new TransformationProcessor(new TransformerEngine(), ['cpf' => ['cpf_to_digits'], 'phone' => ['phone_format']]);
        $input = ['cpf' => '123.456.789-09', 'extra' => ' kept '];

        self::assertSame(['cpf' => '12345678909', 'extra' => ' kept '], $processor->process($input));
        self::assertSame(['cpf' => '12345678909', 'extra' => ' kept '], $processor->transform($input));
    }

    #[Test]
    public function theProcessorTransformsArraysOnly(): void
    {
        $processor = new TransformationProcessor(new TransformerEngine(), ['cpf' => ['cpf_to_digits']]);

        foreach (['123.456.789-09', new stdClass(), null] as $input) {
            try {
                $processor->process($input);
                self::fail('A non-array was transformed.');
            } catch (UnsupportedInputException $exception) {
                self::assertSame(TransformerCode::UnsupportedInput, $exception->errorCode);
                self::assertStringEndsWith(get_debug_type($input) . ' given.', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function theProviderBindsEverySingleton(): void
    {
        $container = new MemoryContainer();
        $configuration = new TransformerConfiguration(trackTransformations: false);
        $events = new ArrayObject();
        (new TransformerServiceProvider($configuration, static function (object $event) use ($events): void {
            $events->append($event::class);
        }))->register($container);
        $container->instance(PropertyInspector::class, new ReflectionInspector());
        $container->instance(PropertyChangeApplier::class, new ReflectionApplier());

        self::assertSame([RuleRegistry::class, TransformerEngine::class, AttributeTransformer::class], $container->singletons);
        $engine = $container->get(TransformerEngine::class);
        self::assertInstanceOf(TransformerEngine::class, $engine);
        self::assertSame($configuration, $engine->configuration);
        self::assertSame($container->get(RuleRegistry::class), $engine->registry);
        self::assertSame([], $engine->transform(['a' => 'A B'], ['a' => ['snake_case']])->transformations);
        self::assertCount(2, $events);

        $transformer = $container->get(AttributeTransformer::class);
        self::assertInstanceOf(AttributeTransformer::class, $transformer);
        $copy = $transformer->transform(new Customer(cpf: '123.456.789-09', phone: '8835210000'))->object;
        self::assertInstanceOf(Customer::class, $copy);
        self::assertSame(['12345678909', '(88) 3521-0000'], [$copy->cpf, $copy->phone]);
        self::assertSame($transformer, $container->get(AttributeTransformer::class));
        (new TransformerServiceProvider())->boot();
    }

    #[Test]
    public function aBindingOfTheWrongTypeIsReported(): void
    {
        $container = new MemoryContainer();
        (new TransformerServiceProvider())->register($container);
        $container->instance(RuleRegistry::class, new stdClass());

        $this->expectExceptionObject(ConfigurationException::invalidBinding(id: RuleRegistry::class, type: 'stdClass'));
        $this->expectExceptionCode(TransformerCode::InvalidBinding->value);

        $container->get(TransformerEngine::class);
    }

    #[Test]
    public function theAttributeTransformerNeedsBothInspectionServices(): void
    {
        $container = new MemoryContainer();
        (new TransformerServiceProvider())->register($container);
        $container->instance(PropertyInspector::class, new ReflectionInspector());
        $container->instance(PropertyChangeApplier::class, 'not an applier');

        $this->expectExceptionObject(ConfigurationException::invalidBinding(id: PropertyChangeApplier::class, type: 'string'));

        $container->get(AttributeTransformer::class);
    }
}
