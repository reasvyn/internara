# Doc Quality — Completeness & Accuracy Checklist

Checklist to ensure documentation is accurate, well-structured, and maintainable.

## Structural Completeness

- [ ] H1 title uses `# Subject — Subtitle` format
- [ ] Metadata block present: `> **Last updated:** YYYY-MM-DD **Changes:** ...`
- [ ] `## Description` section present (1-3 sentence summary)
- [ ] `## Where to Find It` footer section present
- [ ] `---` horizontal rules between major sections
- [ ] Heading hierarchy is correct (`##` → `###` → `####`)

## Two-Tier Separation

- [ ] Conceptual doc (`{module}.md`) contains NO implementation details (file paths, class names, schemas, Actions/Routes tables)
- [ ] Reference doc (`{module}-reference.md`) contains NO design rationale
- [ ] Non-module docs separate "why" (conceptual) from "what" (reference)

## Metadata

- [ ] `**Last updated:**` date is current (YYYY-MM-DD format)
- [ ] `**Changes:**` describes what changed in this revision
- [ ] Date format is correct (not `DD/MM/YYYY` or `MM-DD-YYYY`)

## Link Integrity

- [ ] All `[text](path)` resolve to existing files
- [ ] All `[text](path#anchor)` match existing headings
- [ ] No orphaned references (link target doesn't exist)
- [ ] Relative paths used (not absolute paths)

## Content Quality

- [ ] No content duplication — cross-reference via relative links instead
- [ ] `## Where to Find It` used (not `## References`, `## See Also`, `## Resources`)
- [ ] Code examples use correct syntax highlighting (` ```php `, ` ```bash `, etc.)
- [ ] Tables are properly formatted with aligned columns
- [ ] Indonesian text only in `lang/id/` — all docs are English

## PHPDoc Quality

- [ ] No `@author`, `@version`, `@created`, `@package` tags
- [ ] No redundant `@param`/`@return` when native types are present
- [ ] `@throws` lists specific exception types (not generic `Exception`)
- [ ] One-line for simple methods, multi-line for complex
- [ ] `@see` used for cross-references to related classes

## Destructive Patterns

- [red X] Metadata date not updated when content changed
- [red X] Implementation details leaked into conceptual docs
- [red X] Design rationale leaked into reference docs
- [red X] Content duplicated instead of cross-referenced
- [red X] Broken relative links
- [red X] PHPDoc duplicating native type hints
