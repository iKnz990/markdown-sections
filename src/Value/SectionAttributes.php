<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Value;

final readonly class SectionAttributes
{
    /**
     * @param array<string, string> $values
     */
    private function __construct(private array $values)
    {
    }

    /**
     * @param array<string, string> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->values;
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->values[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function toMarkdown(): string
    {
        if ($this->values === []) {
            return '';
        }

        $parts = [];
        foreach ($this->values as $key => $value) {
            $parts[] = $key . '="' . $this->escape($value) . '"';
        }

        return '{' . implode(' ', $parts) . '}';
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
