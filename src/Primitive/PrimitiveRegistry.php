<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Primitive;

use Nocturnal\MarkdownSections\MarkdownDocument;

final readonly class PrimitiveRegistry
{
    /**
     * @param array<string, PrimitiveDefinition> $definitions
     */
    public function __construct(private array $definitions = [])
    {
    }

    public function register(PrimitiveDefinition $definition): self
    {
        return new self([...$this->definitions, $definition->type => $definition]);
    }

    public function get(string $type): ?PrimitiveDefinition
    {
        return $this->definitions[$type] ?? null;
    }

    public function validate(MarkdownDocument $document, bool $reportUnknown = false): PrimitiveValidationResult
    {
        $issues = [];

        foreach ($document->sections() as $section) {
            $definition = $this->get($section->type);

            if ($definition === null) {
                if ($reportUnknown) {
                    $issues[] = new PrimitiveIssue(
                        'unknown_primitive',
                        sprintf('Primitive "%s" is not registered.', $section->type),
                        $section->type,
                        $section->index,
                        $section->span->startLine,
                    );
                }

                continue;
            }

            array_push($issues, ...$definition->validate($section));
        }

        return new PrimitiveValidationResult($issues);
    }
}
