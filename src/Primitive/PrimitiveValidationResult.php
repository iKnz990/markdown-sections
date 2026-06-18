<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Primitive;

final readonly class PrimitiveValidationResult
{
    /**
     * @param list<PrimitiveIssue> $issues
     */
    public function __construct(private array $issues)
    {
    }

    public function isValid(): bool
    {
        return $this->issues === [];
    }

    /**
     * @return list<PrimitiveIssue>
     */
    public function issues(): array
    {
        return $this->issues;
    }

    /**
     * @return list<PrimitiveIssue>
     */
    public function issuesForSection(int $sectionIndex): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn(PrimitiveIssue $issue): bool => $issue->sectionIndex === $sectionIndex
        ));
    }
}
