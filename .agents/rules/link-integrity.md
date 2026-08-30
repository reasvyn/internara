# Link Integrity — Resolvable References, No Duplicated Content

## Intent

Every cross-reference in a doc must resolve to a real target, and no content may be duplicated
across docs. When a doc needs a fact that lives elsewhere, it links to it — it never copies it.

## Rationale

Two failures destroy a doc system:

1. **Broken links.** A `[text](path)` that points to a renamed or deleted file, or an anchor that no
   longer matches a heading, is a promise the doc cannot keep. Every broken link is a reader
   (human or agent) sent to a dead end, and — worse — a silent signal that the docs are not being
   maintained, so readers stop trusting even the valid parts.
2. **Duplicated content.** When the same fact is copied into two docs, the copies drift: one gets
   fixed, the other doesn't, and now there are two competing "truths". A reader cannot tell which is
   authoritative. The fix is always the same: keep the fact in **one** authoritative location and
   cross-reference it with a relative link.

## How to Apply

### Relative Links

All internal links use relative paths from the current file's location:

```markdown
[Media Library](media-library.md)           # same directory
[Action Pattern](../guides/arch/action-pattern.md)  # up one, then down
[User Module](modules/user.md)              # from docs/index.md
```

### Anchor Links

Anchor links match the exact section heading (lowercased, spaces → hyphens):

```markdown
[Token Security](setup.md#token-security)  # matches ## Token Security
```

### Before Committing Doc Changes

Verify:
1. Every `[text](path)` resolves to an existing file.
2. Every `[text](path#anchor)` matches an existing heading.
3. No content is duplicated — use cross-references instead.

### Content Duplication Rule

**Never duplicate content across docs.** If two docs need the same information:

- Keep it in the **authoritative** location.
- Cross-reference from the other doc with a relative link.

**Examples:**
- S3 configuration → authoritative in `filesystem.md`, `media-library.md` references it.
- Testing conventions → authoritative in `docs/guides/arch/testing-pattern.md`, skills reference it.
- Module overview → authoritative in `docs/refs/modules/{module}.md`, reference doc links to it.

## Anti-Patterns & Pitfalls

- **Copy-paste instead of link:** pasting an S3 config block into a second doc because "it's
  convenient". The copies diverge on the next S3 change.
- **Absolute paths:** linking `/docs/guides/arch/action-pattern.md` from a nested doc. The link
  resolves only from the repo root and breaks when docs move.
- **Stale anchors:** linking `setup.md#token-security` after the heading was renamed to
  `## Token Handling`. Update the anchor or the heading.
- **Linking to a file you renamed/deleted this change** without updating the referencing docs —
  the scanner catches it, but only after you've broken it.
- **Bare URL links to internal files:** linking to `https://github.com/.../docs/x.md` instead of the
  relative path — the relative path survives renames and forks.

## Verification / Detection

- `python3 tools/scan_doc_links/cli.py` — validates every relative link and in-page/file anchor across
  `docs/`, `.agents/context/`, `.agents/memory/`, `README.md`, and `AGENTS.md`; reports `BROKEN_FILE_LINK` and
  `BROKEN_ANCHOR` findings with file and line.
- Grep for duplicated blocks across docs before committing: `grep -rn "S3" docs/guides/infra/`.
- If you must duplicate a small fact despite the rule, add a cross-reference note pointing to the
  authoritative source — but prefer the link-only approach.
