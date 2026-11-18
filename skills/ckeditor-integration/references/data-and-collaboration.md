# 2 and 3. Where the data lives and how it is stored

## HTML is the contract

**First check what the previous editor stores.** If it is not HTML (Draft.js or
Slate JSON, Markdown, a custom block format), decide before anything else:
convert once with a migration command (keep the original until the round trip
is proven), or convert on load and save (Markdown has a CKEditor plugin; JSON
formats need your own converter). Running two formats side by side needs a
format marker per field.

### Migrating existing content

- Check the storage column type too: JSON to HTML can be a schema migration
  (json to text column), not only a format conversion.
- Every consumer of the field (frontend, APIs, search, exports) must render
  HTML after the switch. Agree on that with whoever owns them before running.
- The migration command: runs on a database copy first, supports dry run with
  a diff report per record, works in batches and can resume, keeps the
  original value (backup column or dump), and has a written rollback.
- Sample real records (legacy edge cases included) for the round-trip test
  below before running on everything.

Most hosts store a string of HTML per field. CKEditor reads and writes HTML. The
risk is the markup between them:

- **Markup CKEditor does not model** is dropped on load. Anything the previous
  editor or the host's backend relies on (host reference attributes, classes,
  ids, inline styles, custom elements) needs General HTML Support `htmlSupport.allow`
  rules or a dedicated converter plugin. List them from real stored content and
  from the previous editor's source.
- **Markup the host does not allow** is dropped on save. Many hosts sanitize
  rich text server side (an HTML sanitizer, purifier, allowlist) and do it
  silently. Find that allowlist and compare it with what your toolbar can
  produce. Typical casualties: `code`, `pre`, `figure`, `figcaption`, table
  attributes, `data-*` attributes. Either extend the host allowlist (document
  it) or keep those features off the toolbar.
- **Normalization** makes CKEditor's output differ from the stored string even
  when nothing changed (attribute order, whitespace, wrapping). Do not emit a
  change on load; compare against the last emitted value (see `editor-slot.md`).

### Round-trip test

On a corpus of real content: load into CKEditor, `getData()`, save through the
host, reload. Diff each step. Do it once with the toolbar's own output (every
button used) and once with legacy content.

## Where the data lives

Without collaboration features the host field is the only source of truth, and
the host's revisions, versions, workflows and locking apply unchanged.

With CKEditor real-time collaboration, comments, track changes or revision
history, part of the state lives in CKEditor Cloud Services (or your on-premises
server). Decide explicitly:

| Question | Decide |
|---|---|
| Source of truth while a session is open | Usually the collaboration session; the host field is a snapshot written on save |
| Who saves | Host save stays the commit point; every participant's save writes the same session data |
| External resets | **Disable `setData()` from host value changes while real-time collaboration is on**: it forks the shared state |
| Channel id | One per record **and** field, unique across record types, languages and nested blocks, for example `<host>-<recordType>-<recordId>-<fieldPath>`; allow an explicit per-field override |
| Comments and suggestions storage | Cloud Services, or the host database through CKEditor's adapters; decide before launch, migration later is costly |
| Host locking | Align or disable the host's record lock for collaborative fields, or users cannot co-edit at all |
| Stale sessions | What a reopened record loads when the channel has data newer or older than the host field |

Comment and suggestion markers live inside the HTML as markup. The host
sanitizer must keep them, or the threads lose their anchors.

## AI on records with several fields

A record often has several rich text fields. If the AI should see all of them
("summarize this product", "fix the intro"), share one CKEditor `Context` per
record across the field editors, give each editor a root title and description
(the field label), and reference count the context so it is destroyed when the
record closes. One AI chat per field is the simpler default when fields are
independent. Check the current multi-editor AI support in the live docs.
