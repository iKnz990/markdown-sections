<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Primitive;

use InvalidArgumentException;
use Nocturnal\MarkdownSections\Parser\SectionSyntax;
use Nocturnal\MarkdownSections\Value\Section;

final readonly class PrimitiveDefinition
{
    /**
     * @param list<string> $requiredAttributes
     * @param list<string> $optionalAttributes
     */
    public function __construct(
        public string $type,
        private array $requiredAttributes = [],
        private array $optionalAttributes = [],
        private bool $allowUnknownAttributes = true,
        private bool $requiresContent = false,
    ) {
        if (! SectionSyntax::isValidType($type)) {
            throw new InvalidArgumentException("Invalid primitive type \"$type\".");
        }

        $this->assertValidAttributeNames($requiredAttributes);
        $this->assertValidAttributeNames($optionalAttributes);
    }

    public static function type(string $type): self
    {
        return new self($type);
    }

    public function requireAttributes(string ...$names): self
    {
        return new self(
            $this->type,
            $this->unique([...$this->requiredAttributes, ...$names]),
            $this->optionalAttributes,
            $this->allowUnknownAttributes,
            $this->requiresContent,
        );
    }

    public function optionalAttributes(string ...$names): self
    {
        return new self(
            $this->type,
            $this->requiredAttributes,
            $this->unique([...$this->optionalAttributes, ...$names]),
            $this->allowUnknownAttributes,
            $this->requiresContent,
        );
    }

    public function rejectUnknownAttributes(): self
    {
        return new self(
            $this->type,
            $this->requiredAttributes,
            $this->optionalAttributes,
            false,
            $this->requiresContent,
        );
    }

    public function requireContent(): self
    {
        return new self(
            $this->type,
            $this->requiredAttributes,
            $this->optionalAttributes,
            $this->allowUnknownAttributes,
            true,
        );
    }

    /**
     * @return list<PrimitiveIssue>
     */
    public function validate(Section $section): array
    {
        $issues = [];

        foreach ($this->requiredAttributes as $attribute) {
            if (! $section->hasAttribute($attribute)) {
                $issues[] = new PrimitiveIssue(
                    'missing_attribute',
                    sprintf('Primitive "%s" is missing required attribute "%s".', $this->type, $attribute),
                    $this->type,
                    $section->index,
                    $section->span->startLine,
                );
            }
        }

        if (! $this->allowUnknownAttributes) {
            $allowed = array_flip([...$this->requiredAttributes, ...$this->optionalAttributes]);
            foreach (array_keys($section->attributes->all()) as $attribute) {
                if (! array_key_exists($attribute, $allowed)) {
                    $issues[] = new PrimitiveIssue(
                        'unknown_attribute',
                        sprintf('Primitive "%s" does not allow attribute "%s".', $this->type, $attribute),
                        $this->type,
                        $section->index,
                        $section->span->startLine,
                    );
                }
            }
        }

        if ($this->requiresContent && trim($section->content) === '') {
            $issues[] = new PrimitiveIssue(
                'empty_content',
                sprintf('Primitive "%s" requires content.', $this->type),
                $this->type,
                $section->index,
                $section->span->startLine,
            );
        }

        return $issues;
    }

    /**
     * @param list<string> $names
     */
    private function assertValidAttributeNames(array $names): void
    {
        foreach ($names as $name) {
            if (preg_match('/\A[A-Za-z_][A-Za-z0-9_-]*\z/', $name) !== 1) {
                throw new InvalidArgumentException("Invalid primitive attribute \"$name\".");
            }
        }
    }

    /**
     * @param array<int|string, string> $values
     * @return list<string>
     */
    private function unique(array $values): array
    {
        return array_values(array_unique($values));
    }
}
