<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Integration;

use ArrayObject;
use KaririCode\Contract\Processor\Processor;
use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\AttributeTransformer;
use KaririCode\Transformer\Core\InMemoryRuleRegistry;
use KaririCode\Transformer\Core\TransformerEngine;
use KaririCode\Transformer\Event\TransformationCompletedEvent;
use KaririCode\Transformer\Integration\TransformationProcessor;
use KaririCode\Transformer\Tests\Support\Customer;
use KaririCode\Transformer\Tests\Support\ReflectionApplier;
use KaririCode\Transformer\Tests\Support\ReflectionInspector;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Integration: CustomerImportFlowTest ──────────────────────────────
// A customer import converted end to end, in a pipeline and as a DTO.
// ─────────────────────────────────────────────────────────────────────

#[CoversNothing]
final class CustomerImportFlowTest extends TestCase
{
    #[Test]
    public function anImportPayloadIsConvertedWithACustomRule(): void
    {
        $registry = InMemoryRuleRegistry::withDefaults();
        $registry->register('upper', new readonly class implements TransformationRule {
            public function transform(mixed $value, TransformationContext $context): mixed
            {
                return is_string($value) ? mb_strtoupper($value, 'UTF-8') : $value;
            }

            public function name(): string
            {
                return 'app.upper';
            }
        });
        $durations = new ArrayObject();
        $engine = new TransformerEngine(registry: $registry, observer: static function (object $event) use ($durations): void {
            if ($event instanceof TransformationCompletedEvent) {
                $durations->append($event->durationMs);
            }
        });
        $rules = [
            'customers' => ['csv_to_array'],
            'settings' => ['json_decode', 'flatten'],
            'meta.created' => ['date_to_iso8601'],
            'meta.source' => ['upper', 'kebab_case'],
            'meta.total' => ['number_to_words'],
            'meta.missing' => ['snake_case'],
        ];
        $input = [
            'customers' => "name,cpf,phone\r\nAna Silva,123.456.789-09,\"+55 (88) 99999-0000\"\r\n\"Silva, Bia\",987.654.321-00,8835210000\r\n",
            'settings' => '{"theme": {"mode": "dark", "font": 14}, "lang": "pt-BR"}',
            'meta' => ['created' => '28/11/2026', 'source' => 'crm Export', 'total' => 2],
        ];

        $result = $engine->transform($input, $rules);

        self::assertSame([
            'customers' => [
                ['name' => 'Ana Silva', 'cpf' => '123.456.789-09', 'phone' => '+55 (88) 99999-0000'],
                ['name' => 'Silva, Bia', 'cpf' => '987.654.321-00', 'phone' => '8835210000'],
            ],
            'settings' => ['theme.mode' => 'dark', 'theme.font' => 14, 'lang' => 'pt-BR'],
            'meta' => ['created' => '2026-11-28T00:00:00+00:00', 'source' => 'crm-export', 'total' => 'two'],
        ], $result->transformed);
        self::assertSame(['customers', 'settings', 'meta.created', 'meta.source', 'meta.total'], $result->transformedFields());
        self::assertSame(['app.upper', 'string.kebab_case'], array_map(static fn($change): string => $change->ruleName, $result->transformationsFor('meta.source')));
        self::assertSame($input, $result->original);
        self::assertCount(1, $durations);

        $rows = array_map(
            static fn(array $row): array => $engine->transform($row, ['name' => ['snake_case'], 'cpf' => ['cpf_to_digits'], 'phone' => ['phone_format']])->transformed,
            $result->transformed['customers'],
        );

        self::assertSame([
            ['name' => 'ana_silva', 'cpf' => '12345678909', 'phone' => '(88) 99999-0000'],
            ['name' => 'silva_bia', 'cpf' => '98765432100', 'phone' => '(88) 3521-0000'],
        ], $rows);
    }

    #[Test]
    public function aPipelineConvertsBetweenSteps(): void
    {
        $sum = new readonly class implements Processor {
            public function process(mixed $input): mixed
            {
                return is_array($input) && is_array($input['amounts'] ?? null) ? ['total' => array_sum($input['amounts'])] : $input;
            }
        };
        $pipeline = [
            new TransformationProcessor(new TransformerEngine(), ['amounts' => ['json_decode']]),
            $sum,
            new TransformationProcessor(new TransformerEngine(), ['total' => [['currency_format', ['prefix' => 'R$ ', 'dec_point' => ',', 'thousands' => '.']]]]),
        ];
        $run = static function (mixed $input) use ($pipeline): mixed {
            foreach ($pipeline as $processor) {
                $input = $processor->process($input);
            }

            return $input;
        };

        self::assertSame(['total' => 'R$ 1.500,75'], $run(['amounts' => '[1000, 500.75]']));
    }

    #[Test]
    public function aDtoIsConvertedIntoACopyThatNeedsNoMoreConversion(): void
    {
        $transformer = new AttributeTransformer(new TransformerEngine(), new ReflectionInspector(), new ReflectionApplier());
        $customer = new Customer(cpf: '987.654.321-00', phone: '11.98765.4321', card: '5500000000000004', preferences: '{"notifications": {"email": true}}');

        $copy = $transformer->transform($customer)->object;
        $again = $transformer->transform($copy);

        self::assertInstanceOf(Customer::class, $copy);
        self::assertSame(['98765432100', '(11) 98765-4321', '************0004', ['notifications.email' => true]], [$copy->cpf, $copy->phone, $copy->card, $copy->preferences]);
        self::assertSame('987.654.321-00', $customer->cpf);
        self::assertSame($copy, $again->object);
        self::assertFalse($again->result->wasTransformed());
    }
}
