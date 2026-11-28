# KaririCode Transformer

[![CI](https://github.com/KaririCode-PHP/transformer/actions/workflows/ci.yml/badge.svg)](https://github.com/KaririCode-PHP/transformer/actions/workflows/ci.yml)
![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4)
![ARFA 1.3 level 1](https://img.shields.io/badge/ARFA-1.3%20level%201-blue)
![License MIT](https://img.shields.io/badge/license-MIT-green)

Rule-based data transformation for PHP 8.4: 32 built-in rules — case
conversion, masking, CSV per RFC 4180, JSON, Base64 and hashes, dates,
numbers in words, array restructuring and Brazilian documents, the
alphanumeric CNPJ included — applied to arrays by a rule map or to
readonly DTOs by `#[Transform]` attributes. Rules run in order, every
change is recorded, nested fields stay nested, and a value a rule
cannot convert without loss comes back as it went in; a rule written
down wrongly is the application's mistake and throws a coded exception.
Part of the KaririCode Framework; Spec V4.3.3, ARFA 1.3 level 1.

## 1. Features

- **32 rules** in seven groups: string, data, encoding, date, numeric,
  structure, Brazilian (SPEC-002).
- **Case conversion by words** — one Unicode-aware splitter for
  camel, Pascal, snake and kebab case, so "helloWorld", "HTTPServer"
  and "São Paulo" convert as people read them (ADR-005).
- **Lossless** — RFC 4180 CSV with quoted line breaks, big JSON
  integers kept as strings, no record dropped or cut, strict dates in an
  explicit time zone; what cannot be converted whole comes back
  unchanged (ADR-008).
- **Chained rules, recorded changes** — each rule receives the previous
  one's output; every change is a `FieldTransformation` (field, rule,
  before, after) in an immutable result (ADR-002).
- **Rule maps** — an alias (`'snake_case'`), a rule instance, or
  `[alias or rule, parameters]`; fields by dotted path, written back
  where they were read; absent fields are not created.
- **Attributes** — `#[Transform]` on a DTO, read through the
  kariricode/contract `PropertyInspector` and written into a **copy**
  through the `PropertyChangeApplier`, so readonly DTOs work;
  kariricode/property-inspector 3 implements both (ADR-003).
- **Strict parameters** — a negative count, an unknown hash algorithm,
  a CSV separator of two characters throw `InvalidRuleException` with a
  TRF code, whatever the input (ADR-004).
- **Integrations** — a kariricode/contract `Processor` and
  `Transformer` for pipelines, a `ServiceProvider` for containers, an
  observer that receives start and completion events (ADR-006).
- **No dependencies** beyond kariricode/contract and
  kariricode/exception.

## 2. Installation

```bash
composer require kariricode/transformer
composer require kariricode/property-inspector   # only for #[Transform] on objects
```

PHP 8.4 or later.

## 3. Quick Start

```php
<?php

declare(strict_types=1);

use KaririCode\Transformer\Core\TransformerEngine;

require __DIR__ . '/vendor/autoload.php';

$engine = new TransformerEngine();

$result = $engine->transform(
    [
        'column' => 'First Name',
        'cnpj' => '12.ABC.345/01DE-35',
        'phone' => '+55 88 99999 0000',
        'settings' => '{"theme": {"mode": "dark"}, "order": 12345678901234567890}',
        'import' => "name,note\r\nAna,\"a, b\"\r\n",
        'total' => 1234,
    ],
    [
        'column' => ['snake_case'],
        'cnpj' => ['cnpj_to_digits'],
        'phone' => ['phone_format'],
        'settings' => ['json_decode', 'flatten'],
        'import' => ['csv_to_array'],
        'total' => ['number_to_words'],
        'missing' => ['camel_case'],
    ],
);

echo json_encode($result->transformed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
```

```json
{
    "column": "first_name",
    "cnpj": "12ABC34501DE35",
    "phone": "(88) 99999-0000",
    "settings": {
        "theme.mode": "dark",
        "order": "12345678901234567890"
    },
    "import": [
        {
            "name": "Ana",
            "note": "a, b"
        }
    ],
    "total": "one thousand two hundred and thirty-four"
}
```

The order number keeps all its digits, the quoted comma stays in its
field, and `missing` is not in the input, so it is not created.
`$result->transformedFields()` lists the six fields that changed.

## 4. Rule Maps

A rule map lists, per field, the rule definitions to apply in order:

| Definition | Example |
|---|---|
| alias | `'snake_case'` |
| rule instance | `new MaskRule()` |
| alias with parameters | `['csv_to_array', ['separator' => ';']]` |
| rule with parameters | `[new MaskRule(), ['keep_start' => 0, 'keep_end' => 4]]` |

Field names are dotted paths into nested arrays; a key that contains
dots itself is matched first. Every definition is checked before the
data is read, so a misspelled alias throws even for a field this input
lacks.

Register your own rules under an alias:

```php
use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

final readonly class UpperRule implements TransformationRule
{
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) ? mb_strtoupper($value, 'UTF-8') : $value;
    }

    public function name(): string
    {
        return 'app.upper';
    }
}

$engine->registry->register('upper', new UpperRule());
```

## 5. The Rules

| Group | Aliases |
|---|---|
| String | camel_case, pascal_case, snake_case, kebab_case, mask, reverse, repeat |
| Data | json_encode, json_decode, csv_to_array, array_to_key_value, implode |
| Encoding | base64_encode, base64_decode, hash |
| Date | date_to_timestamp, date_to_iso8601, age, relative_date |
| Numeric | currency_format, percentage, ordinal, number_to_words |
| Structure | flatten, unflatten, pluck, group_by, rename_keys |
| Brazilian | cpf_to_digits, cnpj_to_digits, cep_to_digits, phone_format |

[SPEC-002](docs/spec/SPEC-002-rule-reference.md) lists each rule's
parameters and output. Points worth knowing:

- `csv_to_array` with a header returns the string unchanged when the
  names repeat or a record has another width, instead of padding or
  cutting it; check the type you get back.
- `mask` masks a string too short to keep both ends entirely.
- The date rules read the text in `timezone` (UTC unless set); `age` and
  `relative_date` take `now` for a fixed clock.
- `cnpj_to_digits` keeps letters (IN RFB nº 2.229/2024); the document
  rules convert the shapes kariricode/validator accepts and nothing
  else, and do not check digits.
- `hash` is for checksums and fingerprints, not passwords.

## 6. Attributes

```php
use KaririCode\PropertyInspector\Core\ReflectionChangeApplier;
use KaririCode\PropertyInspector\Core\ReflectionPropertyInspector;
use KaririCode\Transformer\Attribute\Transform;
use KaririCode\Transformer\Core\AttributeTransformer;

final readonly class CustomerInput
{
    public function __construct(
        #[Transform('cpf_to_digits')]
        public string $cpf,
        #[Transform('phone_format')]
        public string $phone,
        #[Transform(['mask', ['keep_start' => 0, 'keep_end' => 4]])]
        public string $card,
        #[Transform('json_decode')]
        public string|array $preferences = '{}',
    ) {}
}

$transformer = new AttributeTransformer($engine, new ReflectionPropertyInspector(), new ReflectionChangeApplier());

$transformed = $transformer->transform($input);
$converted = $transformed->object;   // a new CustomerInput; $input is unchanged
$transformed->result;                // the TransformationResult, with the record of changes
```

`#[Transform]` takes positional definitions only; named arguments
throw. Only properties whose value changed are written into the copy;
when nothing changed, `$transformed->object` is the original instance.
The copy is built without running the constructor, so validate the
copy. A property that a rule turns into another type must declare it
(`string|array` above).

## 7. Results

`TransformationResult` holds `original`, `transformed` and
`transformations`: `get('settings.theme')`, `wasTransformed()`,
`isFieldTransformed()`, `transformedFields()`, `transformationsFor()`,
`transformationCount()`.

```php
$engine = new TransformerEngine(configuration: new TransformerConfiguration(trackTransformations: false));
```

Without tracking, values are transformed the same and no
`FieldTransformation` is kept — useful for bulk imports of large values.

## 8. Exceptions

Input never makes a rule throw. Exceptions are for mistakes in the
rules and for the processor's input type; every exception implements
`TransformerException` and carries a code from TRF 3400–3499:

| Code | Exception | Family | Meaning |
|---|---|---|---|
| 3401–3406 | InvalidRuleException | Configuration | duplicate or unknown alias, malformed definition, missing or invalid parameter, named attribute arguments |
| 3420 | ConfigurationException | Configuration | a container binding of the wrong type |
| 3440 | UnsupportedInputException | Validation | the processor received something other than an array |

## 9. Integrations

```php
use KaririCode\ProcessorPipeline\Pipeline\Pipeline;

// kariricode/processor-pipeline 2, or anything that takes a contract Processor
$pipeline = new Pipeline([
    new TransformationProcessor($engine, ['payload' => ['json_decode']]),
    new TransformationProcessor($engine, ['payload' => ['flatten']]),
]);
$pipeline->process(['payload' => '{"user": {"id": 7}}']);   // ['payload' => ['user.id' => 7]]

// a kariricode/contract container
(new TransformerServiceProvider())->register($container);

// events, for logs and metrics
$engine = new TransformerEngine(observer: static fn (object $event) => $dispatcher->dispatch($event));
```

The processor returns the transformed array; in a processor-pipeline, a
stage that throws reaches the caller as `PipelineExecutionException`
with the stage's exception as `getPrevious()`. The provider binds the
registry, the engine and the `AttributeTransformer` (bind a
`PropertyInspector` and a `PropertyChangeApplier` for it). The observer
receives a `TransformationStartedEvent` (field paths) and a
`TransformationCompletedEvent` (result, duration in ms); the result
holds the input, so redact it before logging.

## 10. Security

Transforming is not validating: decode and convert, then validate the
result before trusting it, and encode at output for the context you
write into. See [SECURITY.md](SECURITY.md) for the threat model and the
ASVS 5.0 mapping.

## 11. Architecture and Quality

| Gate | Result |
|---|---|
| Tests | unit, integration and conformance suites; 100 % of lines covered |
| Static analysis | PHPStan level max (strict and PHPUnit rules), Psalm error level 1 |
| Mutation testing | MSI recorded in [QD-V433](docs/quality/QD-V433-COMPLIANCE.md) |
| Complexity | cyclomatic ≤ 10, nesting ≤ 3, parameters ≤ 4, cognitive ≤ 15 |

The gates run through KaririCode Devkit (`kcode`); see
[CONTRIBUTING.md](CONTRIBUTING.md). Design decisions are in
[docs/adr](docs/adr), the specification in [docs/spec](docs/spec).

## 12. License

MIT — see [LICENSE](LICENSE).
