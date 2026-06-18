<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\CommonMark\Renderer;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use Nocturnal\MarkdownSections\CommonMark\Node\SectionNode;
use Nocturnal\MarkdownSections\Rendering\SectionRenderContext;
use Nocturnal\MarkdownSections\Rendering\SectionRendererRegistry;
use Nocturnal\MarkdownSections\Value\SectionAttributes;

final readonly class SectionNodeRenderer implements NodeRendererInterface
{
    public function __construct(private SectionRendererRegistry $registry)
    {
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
    {
        SectionNode::assertInstanceOf($node);
        \assert($node instanceof SectionNode);

        $context = new SectionRenderContext(
            type: $node->getType(),
            attributes: SectionAttributes::fromArray($node->getAttributes()),
            innerHtml: $childRenderer->renderNodes($node->children()),
            startLine: $node->getStartLine(),
            endLine: $node->getEndLine(),
            rawSource: $node->getRawSource(),
        );

        return $this->registry->render($context);
    }
}
