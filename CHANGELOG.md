# Changelog

All notable changes to `kariricode/transformer` are documented here. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project uses [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] — 2026-11-28

A new line, built from the September 2026 component tarball (32 rules,
ARFA 1.3; never published) and brought to KaririCode Specification
V4.3.3. It replaces the 2.x line (2.0.0, March 2026, on
kariricode/property-inspector 2). Read the upgrade notes below before
updating from either.

### Spec V4.3.3 conformance (2026-11-28)

**Breaking**

- PHP 8.4. Requires `kariricode/contract` ^2.12 and `kariricode/exception`
  ^2.0.2; `kariricode/property-inspector` ^3.0 is a suggestion for
  attribute transformation, no longer a requirement (ADR-003).
- `TransformationRule::getName()` is `name()` (ADR-001).
- `TransformationContext`: the properties `$field`, `$data` and
  `$parameters` replace `getFieldName()`, `getRootData()` and
  `getParameters()` (the tarball's `$fieldName` and `$rootData`);
  `parameter()` replaces `getParameter()`; typed readers
  `intParameter()`, `numberParameter()`, `stringParameter()`,
  `boolParameter()`, `arrayParameter()`, and `hasField()`/`fieldValue()`
  by dotted path. `TransformationContextImpl` is `Core\RuleContext`.
- `TransformerEngine`: all constructor arguments are optional
  (`new TransformerEngine()` uses the 32 built-in aliases); `registry`
  and `configuration` are public properties (`getConfiguration()` is
  gone); an `observer` closure receives events.
- `TransformationResult` is immutable: `original`, `transformed` and
  `transformations` are properties (`getOriginalData()`,
  `getTransformedData()`, `addTransformation()`, `setTransformedValue()`
  and `merge()` are gone) (ADR-002).
- `TransformerConfiguration::$preserveOriginal` is removed; the original
  is always in the result.
- `AttributeTransformer` takes a contract `PropertyInspector` and
  `PropertyChangeApplier` and returns a `TransformedObject` (the object,
  or a transformed copy, and the result) instead of modifying the
  object and returning the result (ADR-003).
- `ProcessorBridge`, which returned `['data' => …, 'result' => …]`, is
  replaced by `Integration\TransformationProcessor` (contract `Processor`
  and `Transformer`, the transformed array); `TransformerServiceProvider`
  implements the contract `ServiceProvider` instead of offering factory
  methods (ADR-006). `TransformationCompletedEvent` has no `$timestamp`.
- Exceptions carry TRF codes 3400–3499 on the kariricode/exception
  families (ADR-004): `InvalidRuleException` (Configuration),
  `ConfigurationException` and `UnsupportedInputException` (new);
  `TransformationException`, never thrown, is removed.
- Misconfiguration throws instead of passing silently: malformed rule
  definitions; a negative `keep_start`, `keep_end`, `times` or
  `decimals`; a `mask` character that is not exactly one character; an
  algorithm `hash_algos()` does not list; a JSON depth outside
  1–2147483647 or JSON_PARTIAL_OUTPUT_ON_ERROR; CSV separators and
  enclosures that are not single bytes, line breaks or are equal; an
  empty `separator` for `flatten` and `unflatten`; `pluck` and
  `group_by` without `field`; an empty or malformed `rename_keys` map;
  an unknown time zone; any parameter of the wrong type (ADR-001,
  ADR-004).
- Case conversion splits words at case changes and treats Unicode
  letters as letters: outputs change for camel-case input, acronyms and
  accented capitals (ADR-005).
- Inputs a rule cannot convert whole come back unchanged instead of
  partly converted: ragged or malformed CSV, lists with a record that
  does not fit, colliding keys in `flatten`, `unflatten` and
  `rename_keys` (ADR-008).
- `cpf_to_digits`, `cnpj_to_digits` and `cep_to_digits` convert the
  shapes kariricode/validator accepts and no longer strip text around a
  number; `json_decode` returns integers beyond PHP's range as strings;
  `age` returns dates after now unchanged.
- Fields the input lacks are no longer created (ADR-002).

**Added**

- Alphanumeric CNPJ in `cnpj_to_digits` (IN RFB nº 2.229/2024) (ADR-008).
- `TransformedObject`; transformation of readonly DTOs into copies.
- `timezone` for every date rule and `now` for `age` and
  `relative_date`; `suffix` for `currency_format`; `dec_point` and
  `thousands` for `percentage`; `depth` and `flags` for the JSON rules;
  dotted paths for `pluck` and `group_by`.
- `number_to_words` covers every int but PHP_INT_MIN (it stopped at
  999), with negatives.
- Events emitted to an optional observer (the 2.x line declared them
  and never emitted them).
- The quality gates through KaririCode Devkit, CI, SBOM, security
  checks (ADR-007); a conformance suite of 30 checks.

**Changed**

- Every definition of a rule map is resolved before the data is read,
  so a misspelled alias shows even when the input lacks the field.
- Rules see the data as given, whatever the order of fields in the map.
- `reverse` reverses grapheme clusters, so accents and emoji sequences
  stay whole.
- `currency_format` and `percentage` put the minus sign before the
  prefix and drop it for amounts that round to zero.
- `base64_decode` accepts only canonical encodings.
- `json_encode` keeps 1.0 as 1.0 by default (JSON_PRESERVE_ZERO_FRACTION).

**Fixed**

- `csv_to_array` split the text at line breaks before parsing, so
  quoted line breaks broke records, and read backslash escapes RFC 4180
  does not have; rows wider than the header made PHP's `array_combine()`
  throw a `ValueError` (2.0.0) or were cut (the tarball); a CSV with only
  a header returned the header as data.
- `camel_case` and `pascal_case` lower-cased the input first, so
  "helloWorld" became "helloworld", and skipped accented first letters;
  `snake_case` and `kebab_case` split every capital ("HTTPServer" became
  "h_t_t_p_server").
- `mask` with `keep_end` 0 appended the whole string after the mask,
  returned strings too short to keep both ends unmasked, and removed the
  middle when `char` was empty.
- Nested fields were read by dotted path but written back as a flat
  key, leaving the nested value untransformed.
- `array_to_key_value` and `group_by` dropped records without the
  field; `implode` wrote nested arrays as empty strings.
- `flatten` lost empty arrays and let colliding paths overwrite each
  other; `unflatten` replaced values with arrays and arrays with values.
- `json_decode` lost the digits of integers beyond PHP's range.
- The date rules took the current time for fields the format did not
  name and accepted dates PHP rolls over (2026-02-30 became
  2026-03-02); `date_to_iso8601` read the text in the server's default
  time zone; `age` gave a positive age for dates in the future.
- `hash` with an unknown algorithm raised PHP's `ValueError` on the
  first string.
- `cnpj_to_digits` left alphanumeric CNPJs unconverted; `phone_format`
  formatted numbers with a carrier prefix and text around them.
- `#[Transform]` on readonly properties threw `Error: Cannot modify
  readonly property`.

## [2.0.0] — 2026-03-05

The March 2026 rewrite on PHP 8.4 and kariricode/property-inspector 2.
1.0.0 to 1.1.0 (October 2024) ran on PHP 8.3 with
kariricode/processor-pipeline 1 and kariricode/property-inspector 1.
See the git history.

## Upgrading from 2.x or the tarball

1. Rename `getName()` to `name()` in custom rules; read parameters with
   the typed readers and the context's properties.
2. Build the engine with `new TransformerEngine()` (or pass your
   registry); bind the provider to a container instead of calling its
   factory methods.
3. Build an `AttributeTransformer` with a `PropertyInspector` and a
   `PropertyChangeApplier`
   (`composer require kariricode/property-inspector`), and use
   `$transformed->object`: the original object is no longer modified.
   Properties that a rule turns into another type must declare it.
4. Read `$result->transformed` instead of `getTransformedData()`; drop
   `preserveOriginal`.
5. Check rule maps that relied on silent defaults, partial conversions
   or extraction from surrounding text; pass `timezone` where dates are
   not in UTC.
6. Catch `TransformerException` or a family instead of SPL exceptions.
7. Expect missing fields to stay missing.
