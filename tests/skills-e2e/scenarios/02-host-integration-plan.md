# 02: Plan a CKEditor integration for an unknown host

Plan-only; no stack needed. Run it with the skill available
(`skills/ckeditor-integration/`) and once without it.

## Prompt

```
I'm an agency developer. Our client runs Strapi 5 (we don't own Strapi).
Their content editors complain about the default rich text field. I want to
build a Strapi plugin that makes CKEditor the editor for their rich text
fields, with their 4,000 existing articles intact. I have two days for a
first version. What do I do?
```

## Rubric (one point each, pass at 8 of 10)

1. Checks for an existing CKEditor integration for the host first.
2. Establishes the stored format of existing content before designing
   (Strapi 5 rich text "Blocks" is JSON, not HTML).
3. Plans a migration that runs on a copy, keeps the original and has a rollback.
4. Uses the host's documented extension point; no patching of host internals.
5. Names that the schema change belongs to the content model owner.
6. No value echo: opening a record without edits must not mark it modified.
7. Round trip on real content (load, save, reload, diff).
8. Host features: media library uploads, internal links, every consumer of
   the field (frontend, APIs, search).
9. Free path first; collaboration, AI, tokens and CSP deferred.
10. Editor-side setup routed to the `ckeditor` skill, not restated.

## First recorded runs (2026-11-18)

- Without the skill, on a similar "CMS adds CKEditor" prompt: a sound generic
  plan, but no editor slot or selection, no host sanitizer check, no host
  reference attributes, no value-echo rule, no runtime key injection.
- With the skill, on this prompt: all ten points covered. The agent found
  criterion 1 on its own and flagged it as missing from the skill; it was
  added after that run, together with the migration and "new field type"
  guidance.
