# ADR-005: Case Conversion by Words, Unicode-Aware

## Status

Accepted — V3.0.0

## Date

2026-11-28

## Context

`camel_case`, `pascal_case`, `snake_case` and `kebab_case` are the most
used rules of the package: they turn form labels into column names,
API fields into properties and titles into identifiers. Both earlier
lines implemented them with byte-level string functions, and each rule
in its own way:

- `camel_case` and `pascal_case` lower-cased the whole string first and
  then upper-cased letters after `-`, `_` and spaces with `ucwords()`.
  An identifier already in camel case lost its word boundaries
  ("helloWorld" became "helloworld"), and `ucwords()` left accented
  first letters alone ("élan vital" became "élanVital" in Pascal case).
- `snake_case` and `kebab_case` put a separator before every ASCII
  capital, so acronyms fell apart ("HTTPServer" became
  "h_t_t_p_server") and accented capitals were not seen as capitals.
- None of the four could be expressed in terms of the others, so
  converting from one case to another was not reversible even for plain
  ASCII identifiers.

### Prior Art

- Lodash `words()` and its case functions: one splitter, four joins.
- Symfony String's `UnicodeString::camel()` and `snake()`.
- Unicode general categories (Lu, Ll, Lt, Lm, Lo, M, N) and the title
  case mapping (UnicodeData, SpecialCasing).

## Decision

1. One splitter, `Core\Words::of()`, feeds the four rules. It inserts a
   boundary between a lower-case letter, a combining mark or a digit and
   the capital that follows it, and between two capitals when the
   second starts a word ("HTTPServer" → HTTP | Server), then splits at
   every run of characters that are not letters, combining marks or
   digits. Digits stay with the word before them ("user2Name" → user2 |
   Name); letters without case (CJK, kana) form words of their own
   runs.
2. The joins: `camel_case` lower-cases the first word and capitalizes
   the rest; `pascal_case` capitalizes every word; `snake_case` and
   `kebab_case` join in lower case with `_` and `-`. Capitalizing uses
   the Unicode title mapping for the first letter and lower-cases the
   rest of the word with `mb_strtolower()`, so "HTTP" becomes "Http" and
   "AÇÃO" becomes "Ação".
3. Invalid UTF-8 is replaced with "?" before splitting, so the rules
   never fail and the replacement separates words.
4. A string with no word becomes the empty string.

## Consequences

### Positive

- Converting between the four cases goes through the same words, so
  `snake_case(camel_case($x))` equals `snake_case($x)` for identifiers.
- Accented letters and acronyms are handled as people read them.

### Negative

- **Breaking:** outputs change where the old rules were wrong, which is
  the point, but also for every all-capitals acronym ("XMLHttpRequest"
  in snake case is now "xml_http_request", not "x_m_l_http_request").
- Mixed acronyms with lower-case letters cannot be split as a human
  would: "IPv6Address" becomes I | Pv6 | Address.
- Characters that are neither letters, marks nor digits (emoji,
  punctuation) are dropped as separators.

## Alternatives Considered

- **Keep `ucwords()` and add `mb_` variants:** fixes accents, keeps the
  lost boundaries of camel-case input.
- **Depend on symfony/string:** a solid implementation and a dependency
  for four rules.
- **Locale-aware case mapping (intl):** Turkish dotless i, for example;
  needs the intl extension, which the package does not require.

## References

- Unicode Standard, chapter 4 (character properties) and §3.13 (default
  case algorithms).
- SPEC-002 §1.
