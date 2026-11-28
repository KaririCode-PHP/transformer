# KaririCode\Transformer — Documentation

## Architecture

```
array data + rule map ────────────────► TransformerEngine::transform(data, fieldRules)
                                           │  TransformationStartedEvent ─► observer
object + #[Transform] ─► AttributeTransformer  every definition resolved: RuleRegistry ─► TransformationRule
   PropertyInspector (kariricode/contract) │  per field present: DataPath, RuleContext
   AttributeRules (the handler) ───────────┤  per rule:  value = transform(value) ─► FieldTransformation
   PropertyChangeApplier ─► a copy ◄───────┤  value written back where it was read
                                           │  TransformationResult (immutable) ─► caller
                                           └  TransformationCompletedEvent ─► observer

shared by the rules: Words (case conversion), CsvReader (RFC 4180), DateValue, NumericValue, IntegerParameter
kariricode/processor-pipeline ─► TransformationProcessor: the transformed array
kariricode/contract container ─► TransformerServiceProvider: registry, engine, AttributeTransformer
```

## Index

| Document | Subject |
|---|---|
| [ADR-001](adr/ADR-001-rules-registry-and-definitions.md) | Stateless rules, a registry of aliases, definitions checked up front, typed parameters |
| [ADR-002](adr/ADR-002-chained-rules-paths-and-results.md) | Chained rules on dotted paths, immutable results, the record of changes |
| [ADR-003](adr/ADR-003-attributes-through-the-inspection-ports.md) | Attribute transformation into copies through the kariricode/contract Inspection ports |
| [ADR-004](adr/ADR-004-error-codes-and-misconfiguration.md) | Error codes TRF 3400–3499; misconfiguration throws; unconvertible values pass untouched |
| [ADR-005](adr/ADR-005-case-conversion-by-words.md) | Case conversion by words, Unicode-aware |
| [ADR-006](adr/ADR-006-observer-and-contract-integrations.md) | The observer, the contract Processor, Transformer and ServiceProvider |
| [ADR-007](adr/ADR-007-toolchain-through-kcode.md) | Quality gates through KaririCode Devkit |
| [ADR-008](adr/ADR-008-lossless-conversions.md) | Lossless conversions: RFC 4180 CSV, structures, JSON, strict dates, masking, the alphanumeric CNPJ |
| [SPEC-001](spec/SPEC-001-transformation-engine.md) | The engine: rules, context, definitions, results, attributes, configuration |
| [SPEC-002](spec/SPEC-002-rule-reference.md) | The 32 rules |
| [QD-V433-COMPLIANCE](quality/QD-V433-COMPLIANCE.md) | Quality record against Spec V4.3.3 |
