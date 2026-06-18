# Primitive Recipes

These recipes are intentionally section-level. They do not require a full Markdown AST editor.

![Section anatomy](assets/section-anatomy.svg)

## CTA

```markdown
:::cta{href="/contact" label="Start"}
Tell us where the workflow breaks.
:::
```

Recommended definition:

```php
PrimitiveDefinition::type('cta')
    ->requireAttributes('href', 'label')
    ->optionalAttributes('variant')
    ->rejectUnknownAttributes()
    ->requireContent();
```

## FAQ

```markdown
:::faq{question="How does review work?"}
Every generated patch is previewed before it is applied.
:::
```

Recommended definition:

```php
PrimitiveDefinition::type('faq')
    ->requireAttributes('question')
    ->rejectUnknownAttributes()
    ->requireContent();
```

## Steps

```markdown
:::steps{id="handoff"}
1. Diagnose the bottleneck.
2. Patch the workflow.
3. Transfer ownership.
:::
```

Recommended definition:

```php
PrimitiveDefinition::type('steps')
    ->requireAttributes('id')
    ->requireContent();
```

## Migration Pattern

1. Parse each Markdown file.
2. Find sections by type.
3. Build a patch for each allowed change.
4. Review the patch preview.
5. Apply only when the checksum still matches.
