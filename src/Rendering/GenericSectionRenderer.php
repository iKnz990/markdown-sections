<?php

declare(strict_types=1);

namespace Nocturnal\MarkdownSections\Rendering;

final class GenericSectionRenderer implements SectionRendererInterface
{
    public function render(SectionRenderContext $context): string
    {
        $attributes = [
            'data-markdown-section' => $context->type,
        ];

        foreach ($context->attributes->all() as $key => $value) {
            $attributes['data-markdown-section-' . $this->safeAttributeName($key)] = $value;
        }

        return '<section' . $this->renderAttributes($attributes) . '>' . $context->innerHtml . '</section>';
    }

    private function safeAttributeName(string $key): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9_-]+/', '-', $key), '-');
    }

    /**
     * @param array<string, string> $attributes
     */
    private function renderAttributes(array $attributes): string
    {
        $html = '';
        foreach ($attributes as $key => $value) {
            $html .= sprintf(
                ' %s="%s"',
                $key,
                htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        return $html;
    }
}
