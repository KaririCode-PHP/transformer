<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

// ── Core: CsvReader ──────────────────────────────────────────────────
// Reads CSV records as RFC 4180 defines them.
// ─────────────────────────────────────────────────────────────────────

/**
 * Splits CSV text into records of fields: a field that starts with the
 * enclosure ends at the next lone enclosure, keeps separators and line
 * breaks inside it, and reads a doubled enclosure as one; other fields
 * end at the separator or the line break. Records end at CRLF, LF or CR;
 * blank lines are skipped and a final line break adds no record; a
 * UTF-8 byte order mark at the start is dropped. Text that cannot be
 * read without guessing — an enclosure left open, or a character after
 * a closing enclosure other than a separator or a line break — gives
 * null instead of a partial result.
 *
 * Preconditions: the separator and the enclosure are different single
 * bytes, neither a line break.
 *
 * Thread-safety: an instance reads one text in one call; records()
 * builds a new one each time.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     CsvReader::records("name,note\r\nAna,\"a, b\"\r\n", ',', '"'); // [['name', 'note'], ['Ana', 'a, b']]
 *
 * References: RFC 4180 §2; ADR-008; SPEC-002 §2.
 */
final class CsvReader
{
    private int $position = 0;

    /** @var list<list<string>> */
    private array $records = [];

    /** @var list<string> */
    private array $fields = [];

    private function __construct(
        private readonly string $text,
        private readonly string $separator,
        private readonly string $enclosure,
    ) {}

    /**
     * @return list<list<string>>|null null when the text is not CSV
     */
    public static function records(string $csv, string $separator, string $enclosure): ?array
    {
        $text = str_starts_with($csv, "\u{FEFF}") ? substr($csv, 3) : $csv;

        return (new self(text: $text, separator: $separator, enclosure: $enclosure))->read();
    }

    /**
     * @return list<list<string>>|null
     */
    private function read(): ?array
    {
        $length = strlen($this->text);

        while ($this->position < $length) {
            if ($this->fields === [] && $this->atLineBreak()) {
                ++$this->position;

                continue;
            }

            $field = $this->text[$this->position] === $this->enclosure ? $this->quoted() : $this->unquoted();

            if ($field === null || !$this->endField($field)) {
                return null;
            }
        }

        return $this->records;
    }

    private function unquoted(): string
    {
        $length = strcspn(string: $this->text, characters: $this->separator . "\r\n", offset: $this->position);
        $field = substr(string: $this->text, offset: $this->position, length: $length);
        $this->position += $length;

        return $field;
    }

    private function quoted(): ?string
    {
        $field = '';
        ++$this->position;

        while (($close = strpos(haystack: $this->text, needle: $this->enclosure, offset: $this->position)) !== false) {
            $field .= substr(string: $this->text, offset: $this->position, length: $close - $this->position);
            $this->position = $close + 1;

            if (($this->text[$this->position] ?? '') !== $this->enclosure) {
                return $field;
            }

            $field .= $this->enclosure;
            ++$this->position;
        }

        return null;
    }

    /**
     * Stores the field and moves past a separator after it; a line break
     * after it ends the record and is skipped by read(). False when
     * anything else follows the field.
     */
    private function endField(string $field): bool
    {
        $this->fields[] = $field;
        $next = $this->text[$this->position] ?? null;

        if ($next === $this->separator) {
            ++$this->position;

            if ($this->position === strlen($this->text)) {
                $this->fields[] = '';
                $this->endRecord();
            }

            return true;
        }

        if ($next !== null && !$this->atLineBreak()) {
            return false;
        }

        $this->endRecord();

        return true;
    }

    private function endRecord(): void
    {
        $this->records[] = $this->fields;
        $this->fields = [];
    }

    private function atLineBreak(): bool
    {
        $character = $this->text[$this->position] ?? '';

        return $character === "\r" || $character === "\n";
    }
}
