# SPEC-002: KaririCode\Transformer 3.0 — Rule Reference

- **Version:** 3.0.0
- **Rules:** 32, all registered by alias in `InMemoryRuleRegistry::withDefaults()`

Every rule returns values of types it does not handle unchanged, and
values it cannot convert without loss unchanged (R-2, R-4); reads its
parameters before the value, so a missing parameter throws TRF_3404 and
one of the wrong type or value TRF_3405 whatever the input (R-3). "Name"
is what `name()` returns and what a `FieldTransformation` records. Text
rules count UTF-8 characters, not bytes.

## 1. String

The four case rules split the string into the same words (ADR-005): at
every run of characters that are not letters, combining marks or digits,
and at the case changes of an identifier ("helloWorld" → hello | World,
"HTTPServer" → HTTP | Server); digits stay with the word before them;
invalid UTF-8 becomes "?" first.

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| camel_case | string.camel_case | — | first word lower case, the others capitalized ("user_first name" → "userFirstName") |
| pascal_case | string.pascal_case | — | every word capitalized ("élan vital" → "ÉlanVital") |
| snake_case | string.snake_case | — | words in lower case joined by `_` ("HTTPServer" → "http_server") |
| kebab_case | string.kebab_case | — | words in lower case joined by `-` ("São Paulo" → "são-paulo") |
| mask | string.mask | `keep_start`, `keep_end` (int ≥ 0, default 3 and 3), `char` (one character, default `*`) | the characters between the kept ends replaced one for one; a string too short to keep both ends masked entirely |
| reverse | string.reverse | — | extended grapheme clusters in reverse order (accents and emoji sequences stay whole) |
| repeat | string.repeat | `times` (int ≥ 0, default 2), `separator` (string, default empty) | `times` copies joined by `separator`; 0 gives the empty string |

## 2. Data

| Alias | Name | Parameters | Result |
|---|---|---|---|
| json_encode | data.json_encode | `flags` (int, default JSON_UNESCAPED_UNICODE \| JSON_UNESCAPED_SLASHES \| JSON_PRESERVE_ZERO_FRACTION, JSON_PARTIAL_OUTPUT_ON_ERROR refused), `depth` (1–2147483647, default 512) | any value as JSON text; a value JSON cannot hold (invalid UTF-8, INF, resources, too deep) unchanged |
| json_decode | data.json_decode | `assoc` (bool, default true), `flags` (int, default JSON_BIGINT_AS_STRING), `depth` | strings of JSON decoded (the literal `null` gives null); other text unchanged |
| csv_to_array | data.csv_to_array | `separator`, `enclosure` (different single-byte characters, not line breaks; default `,` and `"`), `header` (bool, default true) | RFC 4180 records (SPEC-001's `CsvReader`); with a header, a list of maps from unique names to fields, every record as wide as the header; without, a list of lists; an empty list for an empty CSV or a header alone; text that is not CSV, repeated names or ragged records under a header unchanged |
| array_to_key_value | data.array_to_key_value | `key` (string, default `id`), `value` (string, default `name`) | `array_column()` of value by key (a later key replaces an earlier one); unchanged when a record is not an array, lacks a field, or its key is not an int or a string |
| implode | data.implode | `separator` (string, default `,`) | items joined when every item is an int, float, string or Stringable; keys ignored; unchanged otherwise |

## 3. Encoding

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| base64_encode | encoding.base64_encode | — | standard alphabet, padded (RFC 4648 §4) |
| base64_decode | encoding.base64_decode | — | bytes, for canonical Base64 (whitespace ignored, padding optional); text outside the alphabet or with unused bits set unchanged |
| hash | encoding.hash | `algo` (an algorithm `hash_algos()` lists, any case, default `sha256`) | lower-case hex digest; not for passwords |

## 4. Date

All four parse strictly (`Core\DateValue`): fields the format does not
name are zero, dates PHP would move and trailing characters are not
dates, and the text is read in `timezone` (an identifier, default UTC)
unless it names its own zone. `now` (a DateTimeInterface, default the
current time) fixes the clock.

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| date_to_timestamp | date.to_timestamp | `format` (default `Y-m-d`), `timezone` | Unix timestamp (int) |
| date_to_iso8601 | date.to_iso8601 | `from` (default `d/m/Y`), `timezone` | DATE_ATOM with the offset (`2026-11-28T00:00:00-03:00`); a date that names its offset keeps it |
| age | date.age | `from` (default `Y-m-d`), `timezone`, `now` | whole years to `now`; dates after `now` unchanged |
| relative_date | date.relative | `from` (default `Y-m-d H:i:s`), `timezone`, `now` | "just now" under a minute, else "N minute(s)/hour(s)/day(s)/month(s)/year(s) ago" or "… from now", with a month of 30 days and a year of 365 |

## 5. Numeric

Numbers are read by one reader (`Core\NumericValue`): ints, finite
floats and numeric strings; booleans, INF, NAN and other strings are
not numbers. `ordinal` and `number_to_words` take integral numbers only
(42, 42.0, "42", "1e3").

| Alias | Name | Parameters | Result for numbers |
|---|---|---|---|
| currency_format | numeric.currency_format | `decimals` (int ≥ 0, default 2), `dec_point` (default `.`), `thousands` (default `,`), `prefix`, `suffix` (default empty) | rounded, formatted with number_format(); the minus sign before the prefix ("-R$ 1.234,50"); an amount that rounds to zero has no sign |
| percentage | numeric.percentage | `decimals` (int ≥ 0, default 2), `dec_point`, `thousands`, `suffix` (default `%`) | the number times 100, formatted the same way ("12.50%") |
| ordinal | numeric.ordinal | — | the number with its English suffix (1st, 2nd, 3rd, 11th, 21st, 101st, -1st) |
| number_to_words | numeric.number_to_words | — | English words, short scale, British "and" ("one thousand two hundred and thirty-four"; "minus …"); every int but PHP_INT_MIN |

## 6. Structure

| Alias | Name | Parameters | Result for arrays |
|---|---|---|---|
| flatten | structure.flatten | `separator` (non-empty string, default `.`) | leaves keyed by the joined path; empty arrays are leaves; unchanged when two paths collide |
| unflatten | structure.unflatten | `separator` (non-empty, default `.`) | keys split into nested arrays (integer keys kept, merged into array values); unchanged when two keys need the same place |
| pluck | structure.pluck | `field` (string, required; a dotted path, literal keys first) | the list of each record's field, null where it is missing |
| group_by | structure.group_by | `field` (string, required; a dotted path) | records grouped under their field's value, in order; unchanged when a record is not an array or its field is missing or not an int or a string |
| rename_keys | structure.rename_keys | `map` (non-empty array of old key => new key, names int or string) | top-level keys renamed in place, order kept; unchanged when a new name meets an existing key |

## 7. Brazilian

| Alias | Name | Parameters | Result for strings |
|---|---|---|---|
| cpf_to_digits | brazilian.cpf_to_digits | — | `\A\d{3}\.?\d{3}\.?\d{3}-?\d{2}\z` → the 11 digits; else unchanged |
| cnpj_to_digits | brazilian.cnpj_to_digits | — | `\A[0-9A-Z]{2}\.?[0-9A-Z]{3}\.?[0-9A-Z]{3}/?[0-9A-Z]{4}-?\d{2}\z` → the 14 characters, letters kept (IN RFB nº 2.229/2024); else unchanged |
| cep_to_digits | brazilian.cep_to_digits | — | `\A\d{5}-?\d{3}\z` → the 8 digits; else unchanged |
| phone_format | brazilian.phone_format | — | digits, spaces, dots, hyphens and parentheses, optionally after `+55`: 10 digits → "(88) 3521-0000", 11 → "(88) 99999-0000"; an area code starting with 0, another count or other characters unchanged |

The document rules check the shape, not the check digits; validate with
kariricode/validator's `cpf`, `cnpj` and `cep` (ADR-008).
