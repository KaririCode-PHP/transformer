# QD-V433-COMPLIANCE: kariricode/transformer 3.0.0

- **Status: PASS** (automated gates)
- **Date:** 2026-11-28
- **Component:** kariricode/transformer 3.0.0; kariricode/contract ^2.12 (2.12.1 locked), kariricode/exception ^2.0.2 (2.0.2 locked); kariricode/property-inspector ^3.0 suggested
- **Standards:** KaririCode Spec V4.3.3 §29 (gates) and §32 (supply chain); ARFA 1.3 level 1 (Structural); OWASP ASVS 5.0 level 2
- **Authors:** Walmir Silva <walmir.silva@kariricode.org>

Every automated gate of §29.1 passes, locally (§1) and in CI: run
[46160664247](https://github.com/KaririCode-PHP/transformer/actions/runs/46160664247)
passed the gates on PHP 8.4.28 and the suites on PHP 8.5.13 with the
lowest dependencies (kariricode/contract 2.12.0, kariricode/exception
2.0.2), at `134373c`. The status turns FAIL if a gate fails. PASS covers
the automated gates (G1, G4, G5, G6); G3 needs a second reviewer (§8).
No score is assigned (E-006). This is the first quality record of the
3.x line: measured with this release's configurations, the September
2026 tarball had 56 PHPStan errors at level max and 136 Psalm errors at
level 1, with no mutation testing.

## 1. Gates (Spec V4.3.3 §29.1)

Measured locally on 2026-11-28 (§2). CI reported the same tests (460,
4,143 assertions) and the same mutants (907: 891 killed, 9 timed out,
3 errored, 4 escaped).

| Gate | Command | Budget | Measured | Result |
|---|---|---|---|---|
| Tests | `composer test` | all pass, no warning, notice, deprecation or risky test | 460 tests: unit 427, integration 3 (an import payload with a custom rule, a pipeline that converts between steps, a DTO converted into a copy), conformance 30 | pass |
| Conformance | `composer test-conformance` | all pass | 30 tests: `ArfaConformanceTest` 27 (25 rules and 2 documentation checks, §3), `CodeBlockRegistryTest` 3 | pass |
| PHPStan | `composer phpstan` | level max on `src` and `tests`, strict and PHPUnit rules, cognitive complexity ≤ 15 | 0 errors | pass |
| Psalm | `composer psalm` | level 1 on `src`, no baseline | 0 errors; `#[\Override]` required; `NoInterfaceProperties` suppressed in `psalm.xml` (tool limitation, ADR-007) | pass |
| Coding standard | `composer cs-check` | PER-CS (E-045, E-046) | 0 of 81 files to fix | pass |
| Complexity | `composer complexity`, `composer phpstan` | cyclomatic ≤ 10, nesting ≤ 3, parameters ≤ 4 per method of `src`; cognitive ≤ 15 (§13) | 175 methods; no accepted breach (empty baseline); two soft warnings: `RuleContext` has 12 public methods and `TransformationContext` 11 (soft budget 10), the typed parameter readers | pass |
| Mutation | `composer mutation` | MSI ≥ 80 and covered-code MSI ≥ 80 (§19.4) | 907 mutants: 891 killed, 9 timed out, 3 errored, 4 escaped; MSI 99.56 %, covered-code MSI 99.56 %; mutation code coverage 100 % (§7) | pass |
| Security | `composer security` | valid manifest and lock, no advisory, no secret | manifest and lock valid; no advisories in the runtime lock or in `.kcode/composer.lock`; secret scan 0 findings (self-test ok) | pass |

Line coverage of `src` by the unit and integration suites is 100 % (557
of 557 statements; PCOV, `phpunit --coverage-clover`); §29.1 sets no
coverage budget, mutation testing is the gate (§7).

`composer sbom` writes a CycloneDX 1.5 document with 2 runtime components
(kariricode/contract 2.12.1, kariricode/exception 2.0.2).

The README examples were run outside the repository: the quick start as
printed; attribute transformation against kariricode/property-inspector
3.0.0 (`ReflectionPropertyInspector`, `ReflectionChangeApplier`, a
readonly DTO); the pipeline against kariricode/processor-pipeline 2.0.0.

## 2. Environment and Metadata

| Item | Value |
|---|---|
| Runtime | PHP 8.4.28 CLI, PCOV |
| Toolchain | KaririCode Devkit 1.2.2 with the root configs (ADR-007): PHPUnit 11.5.56, PHPStan 2.3.7 with phpstan-strict-rules 2.1.2 and phpstan-phpunit 2.1.4, Psalm 6.19.1, PHP-CS-Fixer 3.95.27, Infection 0.35.5, tomasvotruba/cognitive-complexity 1.2.0 |
| Lock | `composer.lock` resolved from Packagist with a clean Composer home; no path repository |
| `extra.kariricode` (gate G4, errata E-044) | `arfa` 1.3 level 1, `asvs` 5.0 level 2, `spec` 4.3.3, `error-code-range` TRF 3400–3499 |
| Error codes | `TransformerCode`, 8 codes in 3401–3440, prefix TRF; the block is claimed on kariricode/exception's main, not yet in a release (§8) |

## 3. Conformance Rules (§29.2)

`tests/Conformance/ArfaConformanceTest.php`; all 27 pass.

| Rule | Reference |
|---|---|
| Every source file is strict and declares exactly one named type | §25, PSR-4 |
| Docblocks meet the five comment criteria (C1 summary; Preconditions, Thread-safety, Side effects, Example, References) | §17 |
| Section markers are 72 columns wide (sources and tests) | §25 |
| Concrete classes are final | §5 |
| Values (8 listed: the attribute, the settings, the rule context, events and results) are final readonly | §5.2 |
| Readonly classes declare no property hooks, nor `readonly` again inside | E-040 |
| Class constants are typed | §25 |
| Inheritance inside the package is at most two levels deep | §18 |
| No type keeps mutable static state | §5 |
| Names and methods avoid the forbidden patterns (Interface suffix, Manager/Helper/Util, toArray/fromArray/jsonSerialize, magic accessors) | §18, E-001 |
| Public methods are not bare getters (no parameter) or setters (one parameter) | §18 |
| Regular expressions anchor the end with `\z`, never `$` | §24 |
| Call sites with three or more arguments name them (the printf family and calls of a callable the application supplies are exempt) | §30 |
| Contracts are interfaces (3) | §5, §31 |
| Enums are pure unless an external contract reads the value (`TransformerCode`), with the justification written down | E-003 |
| Every directory is a namespace segment | PSR-4 |
| Error codes are unique and inside the reserved block | §8 |
| Exceptions are built only by their named constructors; nothing throws an uncoded exception | §8 |
| Every concrete exception belongs to exactly one contract family, implements the package marker and extends a kariricode/exception base | §8.4 |
| `composer.json` declares the ARFA, ASVS and Spec versions and the code range; the runtime is PHP, kariricode/contract and kariricode/exception; property-inspector is suggested; no `require-dev` | §29.3 G4, E-044, §31 |
| kariricode/contract and kariricode/exception namespaces are imported only where they belong (Inspection by the attribute classes and the provider, DI by the provider, Processor and Transformer by the integration, the exception bases by `Exception\`) | §31 |
| The 32 rules are final readonly and implement `TransformationRule` | §5.2, ADR-001 |
| `object` parameters only where the kariricode/contract Inspection ports have them, and for the copy they return | §30, ADR-003 |
| `composer.json` exposes the eight quality gates | §29.1 |
| Supply chain is pinned: lock committed, CI actions by commit SHA | §32 |

Two further tests check the documentation: 8 ADRs with the seven
canonical sections, this record's status line, the SECURITY.md sections
and the CHANGELOG headings; and that the README, SECURITY.md,
CONTRIBUTING.md, the ADRs, the SPECs, this record and the sources cite
Spec V4.3.3.

`CodeBlockRegistryTest` checks that `TransformerCode` is a valid code
table, that the installed CODE-BLOCKS.md leaves 3400–3499 and the prefix
TRF to this package (and, where it lists the block, that the row
matches), and the documented sub-ranges.

## 4. Error Model (§8)

Every exception implements the marker `TransformerException`, which
declares `$errorCode` (ADR-004): `InvalidRuleException` (Configuration;
3401–3406), `ConfigurationException` (Configuration; 3420) and
`UnsupportedInputException` (Validation; 3440). `getCode()` is the
`TransformerCode` number; messages start with `[TRF_<code>]` and quote
identifiers. Rules never throw because of the input; `transform()`
throws only for mistakes in rules, rule maps and bindings, and the
processor for input that is not an array.

## 5. Security Controls (ASVS 5.0, level 2 declared)

| Control | Test |
|---|---|
| Conversions whole or not at all (V2.2.1) | `DataRulesTest`, `WordsAndCsvTest` |
| Decoding once, canonical Base64, lossless JSON (V1.1.1) | `DataRulesTest` (`json_decode:`, `base64_decode:` cases) |
| Masking fails closed (V14) | `TextRulesTest` (`mask:` cases) |
| Strict dates in an explicit zone (V2.2.1) | `ValueRulesTest`, `RuleContextTest` |
| Misconfiguration throws instead of disabling a rule (V2.2.1) | the `misconfiguredRulesThrow` tests, `TransformerEngineTest::malformedDefinitionsAreRefused`, `definitionsAreCheckedEvenForFieldsTheDataLacks`, `RuleContextTest` |
| Messages without values (V16.5.1) | `ExceptionsTest` |
| Originals never modified; no static state (V15.4) | `AttributeTransformerTest`, the conformance rule on static state |
| No secrets committed | `tools/secret-scan.php` and its self-test |

SECURITY.md keeps the threat model, the residual risks and the
supply-chain controls.

## 6. Judgment Calls

| Topic | Decision | Reason |
|---|---|---|
| Base | the September 2026 tarball as 3.0.0 | the tarball is the reviewed design with events and the observer; 2.0.0 is published, so the new line is a major |
| Case conversion | one Unicode-aware word splitter for the four case rules | the old rules lost camel-case boundaries and split acronyms (ADR-005) |
| Unconvertible values | returned unchanged, never converted in part | a transformer must not lose data nobody asked to lose (ADR-004, ADR-008) |
| CSV | RFC 4180 reader of its own; with a header, unique names and equal widths or the string comes back | the earlier line-based parsing broke quoted line breaks; padding and cutting invent and lose fields (ADR-008) |
| `mask` | short values masked entirely | a masking rule that shows short secrets defeats its purpose (ADR-008) |
| `cnpj_to_digits` | letters kept, the alias kept | IN RFB nº 2.229/2024; renaming the alias would break every rule map for a name (ADR-008) |
| Document rules | the shapes kariricode/validator accepts, no extraction from text | one definition of a CPF across the family; extraction is the sanitizer's `digits_only` |
| Code block | 3400–3499 under TRF, the lowest free block after SAN | registry rule 3 |
| Psalm | `NoInterfaceProperties` suppressed | Psalm 6.19 does not model PHP 8.4 interface properties; PHPStan and PHP check them (ADR-007) |
| PER-CS 3.1 | checked with `@PER-CS3.0` | PHP-CS-Fixer 3.95 implements 3.0 (E-046) |

## 7. Mutation Testing

Infection 0.35.5 with `infection.json5`: every default mutator on `src`,
the unit and integration suites, only the test cases that cover each
mutant, a 5 s time-out. Timed-out and errored mutants count as detected.

| Run | Since the previous run | Mutants | Escaped | MSI | Covered-code MSI |
|---|---|---|---|---|---|
| 1 | first measurement, after the rework | not kept | 30 | 96.79 % | 96.79 % |
| 2 | tests for accented capitals in the case rules, area codes starting with 0, a birth at the very second of `now`, zero and no-decimal amounts, 101st, negative `keep_end` and `decimals`, siblings and integer keys in `unflatten`; the CSV reader skips line breaks at the start of a record instead of measuring CRLF; `relative_date` reads the direction from `DateInterval::$invert`; `currency_format` and `percentage` take the sign from the rounded amount; `repeat` through `str_repeat()`; `number_to_words` decides "and" from the groups left; `implode` without a redundant cast; `unflatten` adds leaves with `+` (the spread renumbered integer keys) | 907 | 4 | 99.56 % | 99.56 % |

Run 2 is this record's. The escaped mutants are equivalent:

| File | Escaped | Why equivalent |
|---|---|---|
| `Core/TransformerEngine.php` | 2 | the divisor that turns nanoseconds into milliseconds, 1,000,000, changed by one: the duration moves by one part per million |
| `Core/CsvReader.php` | 1 | the stop characters of `strcspn()` concatenated in the other order: a set of characters has no order |
| `Rule/Numeric/PercentageRule.php` | 1 | the `(float)` cast before multiplying by 100.0, which Psalm's strict operands need: an int times a float is the same float |

## 8. Open Items

| # | Item | Closes when |
|---|---|---|
| 1 | G3: independent review for P0 findings | a second reviewer signs off |
| 2 | kariricode/exception's main claims TRF 3400–3499; the installed 2.0.2 does not list it, so `CodeBlockRegistryTest` checks that the block is free | a kariricode/exception release ships the row |
| 3 | Release v3.0.0; Packagist is auto-updated and serves 2.0.0 until then | the maintainer tags the release |
| 4 | Escaped mutants (§7) | each is killed by a test or stays documented as equivalent |
