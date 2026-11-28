# SPEC-001: KaririCode\Transformer 3.0 — The Engine

- **Version:** 3.0.0
- **Standards:** KaririCode Specification V4.3.3; ARFA 1.3 level 1 (Structural); OWASP ASVS 5.0 level 2
- **Error code block:** TRF 3400–3499 (ADR-004)

## 1. Purpose

Convert data from one form into another: arrays by a rule map, objects
by `#[Transform]` attributes. Each field passes through its rules in
order; every change is recorded; values a rule cannot convert without
loss pass untouched, and rules written down wrongly are refused. The
rules are specified in SPEC-002.

## 2. Architecture

```
src/
├── Attribute/      Transform
├── Configuration/  TransformerConfiguration
├── Contract/       TransformationRule, TransformationContext, RuleRegistry
├── Core/           TransformerEngine, RuleContext, InMemoryRuleRegistry, AttributeTransformer, AttributeRules;
│                   DataPath, NumericValue, DateValue, IntegerParameter, Words, CsvReader (shared by rules)
├── Event/          TransformationStartedEvent, TransformationCompletedEvent
├── Exception/      TransformerCode, the marker TransformerException and three final exceptions
├── Integration/    TransformationProcessor (kariricode/contract Processor and Transformer)
├── Provider/       TransformerServiceProvider (kariricode/contract ServiceProvider)
├── Result/         TransformationResult, FieldTransformation, TransformedObject
└── Rule/           32 rules in Brazilian, Data, Date, Encoding, Numeric, String, Structure
```

```
transform(data, fieldRules)
  ├── TransformationStartedEvent ─► observer
  ├── for each field: definitions → [rule, parameters] (InvalidRuleException when malformed)
  │     ├── the data lacks the field → skip (nothing is created)
  │     ├── value = DataPath::value(data, field)
  │     └── for each rule: value = transform(value, context(field, parameters))
  │           └── value changed and tracking on → FieldTransformation
  │     └── transformed = DataPath::with(transformed, field, value)
  ├── TransformationResult (immutable: original, transformed, transformations)
  └── TransformationCompletedEvent ─► observer
```

## 3. Rules

`TransformationRule::transform(mixed $value, TransformationContext $context): mixed`
returns the converted value; `name(): string` names the rule in the
record of changes (`string.snake_case`, `data.csv_to_array`, …).
Invariants:

| ID | Invariant |
|---|---|
| R-1 | A rule holds no state between calls; built-in rules are `final readonly`. |
| R-2 | A rule returns a value of a type it does not handle, or one it cannot convert without loss, unchanged; it never throws because of the input. |
| R-3 | A rule reads its parameters before the value, through the typed readers, so a misconfiguration throws whatever the value. |
| R-4 | A rule never returns a partial conversion: what it cannot convert whole it returns as it came. |

## 4. The Context and Field Paths

`RuleContext` implements `TransformationContext`: `field`, `data` (the
whole input, as given), `parameters`; `withField()` and
`withParameters()` return copies (parameters merge, later wins).

| Reader | Accepts | Absent without default | Present with another type |
|---|---|---|---|
| `parameter($name, $default)` | anything | `$default` | — |
| `intParameter()` | int | TRF_3304 | TRF_3305 |
| `numberParameter()` | int, finite float | TRF_3304 | TRF_3305 |
| `stringParameter()` | string | TRF_3304 | TRF_3305 |
| `boolParameter()` | bool | TRF_3304 | TRF_3305 |
| `arrayParameter()` | array | TRF_3304 | TRF_3305 |

A default covers absence only: a parameter given as null is a parameter
of the wrong type. `Core\IntegerParameter` adds the lower bound counts
and decimal places need (`atLeast()`) and the JSON depth range
(`depth()`); `Core\DateValue` reads `timezone` and `now`.

Field paths (`Core\DataPath`): a literal key first, else segments split
at dots, integer keys by their digits. `hasField()` tells a missing
field from one set to null; `fieldValue()` reads null for both.
`DataPath::with()` returns a copy with the value replaced at the path;
it creates the intermediate arrays a missing path needs, and the engine
calls it only for fields the data has.

## 5. Rule Maps, Definitions and Exceptions

`transform(array $data, array $fieldRules): TransformationResult`. Each key
of `$fieldRules` is a field path (integer keys become strings); each
value is a list of definitions:

| Definition | Meaning |
|---|---|
| `'alias'` | the registry's rule, no parameters |
| `TransformationRule` | that rule, no parameters |
| `[alias or rule]` | the same |
| `[alias or rule, parameters]` | with parameters, a map of names to values |

Every definition of the map is resolved before the data is read.

| Code | Class | Family | Named constructors | Raised when |
|---|---|---|---|---|
| 3301 | InvalidRuleException | Configuration | duplicateAlias | an alias is registered twice |
| 3302 | InvalidRuleException | Configuration | unknownAlias | a definition names no registered rule |
| 3303 | InvalidRuleException | Configuration | invalidDefinition, emptyAlias | a malformed definition or an empty alias |
| 3304 | InvalidRuleException | Configuration | missingParameter, missingBounds | a required parameter is absent |
| 3305 | InvalidRuleException | Configuration | invalidParameter | a parameter has the wrong type or value |
| 3306 | InvalidRuleException | Configuration | namedArguments | `#[Transform]` received named arguments |
| 3320 | ConfigurationException | Configuration | invalidBinding | a container binding has the wrong type |
| 3340 | UnsupportedInputException | Validation | notAnArray | the processor received something other than an array |

Messages carry `[TRF_NNNN]`, quote identifiers through
kariricode/exception and name types, never values.

## 6. Results and Modifications

| Type | Content |
|---|---|
| `FieldTransformation` | `field`, `ruleName`, `before`, `after` |
| `TransformationResult` | `original`, `transformed`, `transformations`; `get()`, `wasTransformed()`, `isFieldTransformed()`, `transformedFields()`, `transformationsFor()`, `transformationCount()` |

Invariants:

| ID | Invariant |
|---|---|
| INV-001 | The rules of a field run in the order listed, each on the previous output. |
| INV-002 | A rule whose output is not identical (`!==`) to its input adds one modification, when tracking is on. |
| INV-003 | Modifications keep the order of fields and rules. |
| INV-004 | A field the data lacks is neither transformed nor created. |
| INV-005 | Field paths are dotted; a literal key wins; the value is written where it was read. |
| INV-006 | Rules see the data as given, whatever the order of fields in the map. |
| INV-007 | `original` is the input; results are immutable. |
| INV-008 | `isFieldTransformed()` and `wasTransformed()` compare values, so a change of type counts. |

## 7. Attributes

`#[Transform(...$definitions)]` (repeatable, positional only, TRF_3306
for named arguments). `AttributeTransformer::transform(object $object):
TransformedObject` inspects `Transform` through a kariricode/contract
`PropertyInspector` into `AttributeRules` (repeated attributes join
their rules in order), transforms the collected values, and hands the
properties whose value changed to a kariricode/contract
`PropertyChangeApplier`, which returns a copy. With no change, the
original instance is returned and the applier is not called.
`TransformedObject` holds `object` and `result`.

## 8. Configuration

| Setting | Default | Effect |
|---|---|---|
| `trackTransformations` | true | false records no `FieldTransformation`; values are transformed the same |

## 9. Events and Integrations

- Observer: `TransformationStartedEvent(fields, timestamp)` with the field
  paths of the map, `TransformationCompletedEvent(result, durationMs)`
  measured on the monotonic clock. The completed event carries values.
- `TransformationProcessor(engine, fieldRules)`: `process()` and
  `transform()` return the transformed array; non-arrays throw TRF_3340.
- `TransformerServiceProvider(configuration, observer)`: binds
  `RuleRegistry`, `TransformerEngine` and `AttributeTransformer` as
  singletons; the last resolves the container's `PropertyInspector` and
  `PropertyChangeApplier`. A binding of the wrong type throws TRF_3320.

## 10. ARFA 1.3 and Spec V4.3.3

| Rule | How |
|---|---|
| P1 immutable state | rules, contexts, results, events, settings and the engine are `final readonly`; the registry is mutable until shared; objects are transformed into copies; `CsvReader` keeps its state in one instance per call |
| P2 flow | definitions → rules → modifications → result |
| P4 protocol agnosticism | arrays and objects in, a result out; no HTTP, no I/O |
| P5 observability | coded exceptions; start and completion events |
| E-003 pure enums | TransformerCode is the only enum, backed for `getCode()` |
| §13, §17, §18, §29, §30, §32 | checked by the conformance suite and the gates (ADR-007) |

## 11. File Inventory

| Directory | Files |
|---|---|
| src/Attribute | 1 |
| src/Configuration | 1 |
| src/Contract | 3 |
| src/Core | 11 |
| src/Event | 2 |
| src/Exception | 5 |
| src/Integration | 1 |
| src/Provider | 1 |
| src/Result | 3 |
| src/Rule | 32 |
| tests/Unit | 10 |
| tests/Integration | 1 |
| tests/Conformance | 2 |
| tests/Support | 5 |
