# Security Policy — kariricode/transformer

Part of the KaririCode Framework. Security baseline: OWASP ASVS 5.0, target
level 2 (`extra.kariricode.asvs` in `composer.json`), KaririCode
Specification V4.3.3 §20–§24 and §32.

## Reporting a vulnerability

E-mail **walmir.silva@kariricode.org** with a description, the affected
version and a minimal reproduction. Do not open a public issue for
vulnerabilities. The fix timeline is agreed per report.

## Supported Versions

| Version | Status |
|---|---|
| 3.0.0 | Supported once tagged; QD-V433 PASS (`docs/quality/QD-V433-COMPLIANCE.md`) |
| 2.0.0 and earlier | Not supported; `mask` in 2.x shows short values unmasked and `csv_to_array` can throw for input (see CHANGELOG, Fixed) |

## Threat Model

The package converts data an application received or is about to send:
CSV uploads, JSON payloads, identifiers, documents. The rules, the rule
maps, the attributes and the configuration are trusted; the data is
not: its values, types, sizes and the presence of each field come from
whoever sent it. The threats are a conversion that silently changes or
drops data (a record lost, a number rounded, a date moved), a rule that
reveals what it should hide, a conversion that throws or works without
bound on hostile input, a rule that silently stops working because it
was written down wrongly, and an application that trusts converted data
as validated.

```
client ─► data ─► TransformerEngine ─► rules (pure functions of value and parameters)
                      │                   └── misconfiguration ─► InvalidRuleException (the application's)
                      └── TransformationResult: original, transformed, transformations ─► validator ─► storage or output
```

| Threat | Control in this package | Residual risk | ASVS 5.0 |
|---|---|---|---|
| Data changed or lost in conversion | Conversions are whole or not at all: ragged or malformed CSV, lists with a record that does not fit, colliding keys and big JSON integers come back unchanged or lossless; the record of changes shows what was converted | A value left unchanged is not validated: check the type or validate after the transformer | V2.2.1 |
| Input decoded more than once or in the wrong place | Each decoding rule decodes once (`json_decode`, `base64_decode` canonical only, `csv_to_array`); nothing decodes implicitly | Rule order is the application's: decode before you validate, encode at output | V1.1.1, V1.1.2 |
| Values shown that should be hidden | `mask` replaces every character between the kept ends and masks a value too short to keep both ends entirely | Masking is display-only: it is not encryption or tokenization, and the original stays in the result | V14 (data protection) |
| A rule that stops working because of a typo | Missing or mistyped parameters, unknown aliases, algorithms and time zones, malformed definitions throw with a TRF code, whatever the input; every definition is checked before the data is read | A rule left out of the map is not detected | V2.2.1 |
| Input that throws or works without bound | Rules never throw for input; CSV is read in one pass; JSON depth is bounded (512 unless set) | The size of the input is bounded by the transport: bound request bodies before transforming | V1.3.12 |
| Weak hashes taken for security | `hash` documents that it is for checksums and fingerprints, defaults to sha256 and is not for passwords | md5 and sha1 stay available for compatibility checksums | V11 (cryptography) |
| Values leaking through logs | Exception messages name fields, aliases and types, never values, quoted through kariricode/exception (control characters neutralized); `TransformationStartedEvent` carries field paths only | `TransformationCompletedEvent` and `FieldTransformation` hold values by design: redact before logging | V16.2.5 |
| Shared state between requests | Rules, contexts, results and the engine are immutable; objects are transformed into copies; `CsvReader` keeps state in one instance per call; no static state | A registry is mutable until shared: register at boot | V15.4 |

## ASVS 5.0

Level 2 is the target. The controls above map to these tests:

| Control | Test |
|---|---|
| Whole or unchanged conversions | `DataRulesTest` (`csv_to_array:`, `array_to_key_value:`, `group_by:`, `flatten:`, `unflatten:`, `rename_keys:` cases), `WordsAndCsvTest::textThatIsNotCsvGivesNull` |
| Lossless JSON and canonical Base64 | `DataRulesTest` (`json_decode: big integers keep their digits`, `base64_decode:` cases) |
| Masking fails closed | `TextRulesTest` (`mask:` cases) |
| Strict dates in an explicit zone | `ValueRulesTest` (date cases), `RuleContextTest::datesAreReadStrictlyInTheirZone` |
| Misconfiguration throws | `misconfiguredRulesThrow` in the three rule suites (with a value of another type too), `TransformerEngineTest::malformedDefinitionsAreRefused`, `definitionsAreCheckedEvenForFieldsTheDataLacks`, `RuleContextTest` |
| Messages without values | `ExceptionsTest` |
| Originals never modified | `AttributeTransformerTest`, `CustomerImportFlowTest::aDtoIsConvertedIntoACopyThatNeedsNoMoreConversion` |
| No static state | the conformance rule on static properties and variables |
| No secrets committed | `tools/secret-scan.php` and its self-test |

## Supply Chain

- Runtime dependencies are `kariricode/contract` and
  `kariricode/exception`; `kariricode/property-inspector` is suggested.
  No network, file or process access.
- `composer.lock` is committed, resolved from Packagist with a clean
  Composer home, with no path repository.
- `composer security` validates the manifest and the lock, audits the
  runtime lock and the toolchain lock (`.kcode/composer.lock`) for
  advisories, and scans the tree for secrets.
- `composer sbom` writes a CycloneDX 1.5 document; CI keeps it as a build
  artifact.
- The toolchain is pinned in `devkit.php`; CI installs kcode with a
  verified checksum, pins every action by its full commit SHA and runs
  the dependency review on pull requests.
- Release tags are created by the maintainer (Spec V4.3.3 §32).

## Hardening checklist for applications

- Transform, then validate the result, then encode at output for the
  context; none replaces the others.
- Check the type a conversion returns where it must have happened (a
  CSV that came back as a string was not converted).
- Bound the size of request bodies and uploads before transforming them.
- Register custom rules at boot, then share the engine.
- Redact `TransformationCompletedEvent::$result` before writing it to
  logs, above all for fields you mask.
- Treat `TransformerException` from the Configuration family as a bug in
  the rule map, not as bad input.
