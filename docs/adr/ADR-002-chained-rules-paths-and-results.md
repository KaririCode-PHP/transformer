# ADR-002: Chained Rules on Dotted Paths, Immutable Results and a Record of Changes

## Status

Accepted — V3.0.0

## Date

2026-11-28

## Context

A transformer is a pipeline per field: decode, then flatten; split
into words, then join in snake case. Both earlier lines chained rules
that way and recorded each change as a `FieldTransformation`, which is
worth keeping: the record shows what each step made of a value. Around
that core, four things were wrong:

- `TransformationResult` was mutable: `addModification()`,
  `setTransformedValue()` and `merge()` were public, so any holder could
  rewrite a result after the fact.
- Fields were read by dotted path (`'user.email'` walked into
  `['user' => ['email' => …]]`) but written back as a flat key
  (`$transformed['user.cpf']`), leaving the nested value untransformed
  next to a new top-level key.
- A field the input lacked was added: as null, or as whatever the rules
  made of null (`json_encode` turned it into `"null"`), so transforming changed
  the shape of the data and the next step saw a value instead of a
  missing field.
- `TransformerConfiguration::$preserveOriginal` was documented and read
  by nothing; the original was always kept.

### Prior Art

- kariricode/validator ADR-002 and kariricode/sanitizer ADR-002: an
  immutable result, dotted paths, literal keys first.
- Symfony's form data transformers: one value per field passed through
  an ordered list of conversions.

## Decision

1. The rules of a field run in the order listed; each receives the
   previous rule's output. When a rule returns a value that is not
   identical (`!==`) to what it received, the engine records
   `FieldTransformation(field, ruleName, before, after)`, unless
   `TransformerConfiguration::$trackTransformations` is false.
2. A field name is a dotted path into nested arrays; a key that
   contains dots itself is matched first, so flat form data with dotted
   names works too. The transformed value is written back where it was
   read, in a copy (`Core\DataPath::with()`); integer keys are paths
   like any other.
3. A field the data lacks is skipped and not created. A field present
   with null runs its rules, which leave null alone unless they say
   otherwise (`json_encode` makes it `"null"`).
4. Rules see the data as it was given (`$context->data`), not the copy
   being transformed, so the order of fields in a rule map never changes
   a result.
5. `TransformationResult` is a final readonly value holding `original`,
   `transformed` and `transformations`, with `get()`, `wasTransformed()`,
   `isFieldTransformed()` (by value, so `'42'` becoming `42` counts),
   `transformedFields()`, `transformationsFor()` and `transformationCount()`.
   `preserveOriginal` is removed.

## Consequences

### Positive

- Results can be passed around and logged without defensive copies.
- Nested input stays nested; the absent stays absent, which keeps a
  later "required" check meaningful.
- Rules that recognize their own output leave it alone (`cpf_to_digits`
  on digits, `phone_format` on a formatted number, `flatten` on a flat
  array, `json_decode` on an array), so a DTO converted twice comes back
  as the same instance; the integration suite checks it.

### Negative

- Each changed field copies the arrays along its path.
- With tracking on, a transformation holds the value before and after
  each rule; for bulk imports of large strings, turn tracking off.

## Alternatives Considered

- **Write back through references:** no copies, and it would mutate
  arrays the caller still holds.
- **Keep the flat write:** compatible with 1.2.3 and wrong for every
  nested field.
- **Create absent fields as null:** a stable output shape, at the cost
  of hiding "missing" from the step that runs next.

## References

- kariricode/validator ADR-002; kariricode/sanitizer ADR-002.
- SPEC-001 §4, §6 and §8.
