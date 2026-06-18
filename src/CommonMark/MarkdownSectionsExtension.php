<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\CommonMark;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Nocturnal\MarkdownSections\CommonMark\Node\SectionNode;
use Nocturnal\MarkdownSections\CommonMark\Parser\SectionBlockStartParser;
use Nocturnal\MarkdownSections\CommonMark\Renderer\SectionNodeRenderer;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;

final readonly class MarkdownSectionsExtension implements ExtensionInterface
{
    public function __construct(private ?SectionRendererRegistry $registry = null)
    {
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $registry = $this->registry ?? new SectionRendererRegistry();

        $environment
            ->addBlockStartParser(new SectionBlockStartParser(), 80)
            ->addRenderer(SectionNode::class, new SectionNodeRenderer($registry));
    }
}
