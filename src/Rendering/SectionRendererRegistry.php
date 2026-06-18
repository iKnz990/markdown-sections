<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Rendering;

final readonly class SectionRendererRegistry
{
    /**
     * @param array<string, SectionRendererInterface> $renderers
     */
    public function __construct(
        private array $renderers = [],
        private SectionRendererInterface $fallback = new RawSourceSectionRenderer(),
    ) {
    }

    public function register(string $type, SectionRendererInterface $renderer): self
    {
        $renderers = $this->renderers;
        $renderers[$type] = $renderer;

        return new self($renderers, $this->fallback);
    }

    public function withFallback(SectionRendererInterface $fallback): self
    {
        return new self($this->renderers, $fallback);
    }

    public function render(SectionRenderContext $context): string
    {
        return ($this->renderers[$context->type] ?? $this->fallback)->render($context);
    }
}
