# ADR-008: Lossless Conversions — RFC 4180 CSV, Structures Without Silent Loss, Strict Dates, the Alphanumeric CNPJ

## Status

Accepted — V3.0.0

## Date

2026-11-28

## Context

A transformer changes the form of data; it must not change its content
behind the application's back. The earlier rules did, in several ways:

- `csv_to_array` (2.0.0) split the text at "\n" before parsing, so a
  quoted field with a line break became two broken records, and it read
  backslash escapes RFC 4180 does not have; the tarball cut rows wider
  than the header and padded narrower ones; both returned the header
  row as data when the CSV had no other record.
- `array_to_key_value`, `group_by` and `implode` dropped or blanked
  what they could not handle: records without the key field, keys that
  were not strings (cast, or skipped), nested arrays written as "".
- `flatten` lost empty arrays and let colliding paths overwrite each
  other; `unflatten` replaced a value with an array, or an array with a
  value, when two keys disagreed.
- `json_decode` turned integers beyond PHP's range into floats, losing
  digits (an order number, a snowflake id).
- The date rules parsed without `!`, so the fields a format did not
  name took the current time, PHP rolled impossible dates over
  (2026-02-30 became 2026-03-02), and `date_to_iso8601` read the text in
  the server's default time zone before converting it. `age` turned a
  date in the future into a positive age.
- `mask` returned a string too short to keep both ends unmasked, and
  `keep_end` 0 appended the whole string after the mask.
- `cnpj_to_digits` kept digits only: since IN RFB nº 2.229/2024 the
  twelve characters of root and order may be letters (the Receita
  Federal issues such numbers from July 2026), and those CNPJs were not
  converted at all.

### Prior Art

- RFC 4180 (CSV), RFC 8259 (JSON), RFC 4648 §3.5 (canonical Base64).
- kariricode/validator ADR-008 and kariricode/sanitizer ADR-008: the
  alphanumeric CNPJ and strict dates.

## Decision

1. **CSV.** `Core\CsvReader` implements RFC 4180: quoted fields keep
   separators, line breaks and doubled enclosures; records end at CRLF,
   LF or CR; blank lines and a leading byte order mark are skipped. Text
   that cannot be read without guessing gives no records, and the rule
   returns the string. With a header, names must be unique and every
   record as wide as the header, or the string is returned; a CSV with
   only a header gives an empty list.
2. **Structures.** `array_to_key_value`, `group_by` and `implode` return
   the value unchanged when an item does not fit (no array, a missing
   field, a key that is not an integer or a string, an item that is not
   a scalar or Stringable). `flatten` keeps empty arrays as leaves and
   returns the array unchanged when two paths collide; `unflatten`
   returns it unchanged when two keys need the same place;
   `rename_keys` when a new name meets an existing key.
3. **JSON.** `json_decode` reads big integers as strings by default
   (JSON_BIGINT_AS_STRING); `json_encode` keeps 1.0 as 1.0
   (JSON_PRESERVE_ZERO_FRACTION) and refuses
   JSON_PARTIAL_OUTPUT_ON_ERROR; both return the value unchanged when
   JSON cannot hold it. `base64_decode` accepts only canonical
   encodings.
4. **Dates.** `Core\DateValue` parses with `!`, refuses dates PHP would
   move and trailing characters, and reads the text in the rule's
   `timezone` (UTC unless set) when it names no zone of its own. `age`
   and `relative_date` take an optional `now`, so results do not depend
   on when a test runs; `age` returns a future date unchanged.
5. **Masking fails closed.** `mask` masks a string too short to keep
   both ends entirely, and `keep_end` 0 keeps nothing at the end.
6. **Brazilian documents.** `cpf_to_digits`, `cnpj_to_digits` and
   `cep_to_digits` convert the shapes kariricode/validator accepts (bare
   or formatted, each separator optional) and nothing else: text around
   a number is no longer stripped away. `cnpj_to_digits` keeps the
   letters of an alphanumeric CNPJ, upper case as the RFB writes them.
   None of them checks digits.

## Consequences

### Positive

- What comes out means what went in; what could not be converted comes
  out as it went in, and the record of changes shows which is which.
- Uploads with quoted line breaks, big ids and alphanumeric CNPJs work.

### Negative

- **Breaking:** inputs that used to produce partial results now come
  back unchanged (a ragged CSV, a list with one bad record), so code
  that consumed the partial result must check the type it receives.
- `cpf_to_digits` no longer extracts a number from surrounding text:
  run kariricode/sanitizer's `digits_only` first when that is wanted.
- Big integers decoded from JSON are strings unless `flags` says
  otherwise.

## Alternatives Considered

- **Pad and cut CSV rows to the header:** the tarball's choice; it
  silently invents and loses fields.
- **Keep `cnpj_to_digits` numeric and add a new rule for letters:** two
  rules for one document, and the old one failing quietly from July
  2026.
- **Throw on unconvertible input:** see ADR-004.

## References

- RFC 4180; RFC 8259; RFC 4648 §3.5; IN RFB nº 2.229/2024.
- kariricode/validator ADR-008; kariricode/sanitizer ADR-008.
- SPEC-002 §2–§7.
