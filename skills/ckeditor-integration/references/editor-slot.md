# 1. Where the editor plugs in

## Find the slot

Look for, in this order:

1. **A documented editor extension point** (an editor registry, a field widget
   type, a "rich text provider" setting). Use it.
2. **An undocumented single component slot** that the built-in editor and other
   editor plugins already override (a component registry entry, a DI service).
   Usable, but note it in the integration's limitations and ask the host to
   document it (point 7).
3. **A new field type instead of a swap**: the host lets plugins add field types
   (custom fields) but not replace the editor of the built-in one. Then every
   field must be switched to the new type in the content schema, which is a
   schema change owned by whoever owns the content model (often the client),
   plus a data migration if the storage type differs (see
   `data-and-collaboration.md`). Agree on it before building.
4. **No slot**: the editor is hardcoded in the host's field components. The host
   must change first (add a slot or a provider config); patching host internals
   from a plugin breaks on the next host release. Raise it upstream.

Read the existing editor plugin of the host, if any. It shows the registration
call, the service ids, the props and the lifecycle in a few lines, faster than
archaeology in the host's source.

## Selection when several editors are installed

Hosts rarely arbitrate a slot: every editor plugin overrides it and the last
registration wins. Make the choice explicit:

- One config value decides (`editor: ckeditor | <other> | default`), read at
  runtime.
- Make your registration order deterministic. If the host orders plugins by
  priority, record the built-in editor before anyone overrides it (lowest
  priority) and apply the configured choice after everyone (highest priority).
- When the configured editor is missing, fall back with a console warning.
  Never leave the slot empty.

## Map every editing surface

List each place the host renders rich text and check that each goes through
the slot: structured record fields, page or template regions, inline blocks,
grid cells, modals. Surfaces that run in an **iframe** (page edit mode is a
common case) need the editor code, its CSS and its config loaded in that frame
too; check how the host bootstraps plugins there.

## What the host passes in

Write down the real props per surface, not the typed ones:

| Need | Usually passed | Often missing |
|---|---|---|
| Value, change callback | yes | |
| Read only, placeholder, size, length limit | yes | |
| Per-field editor config | yes, often shaped for the previous editor | a mapping to CKEditor options |
| **Field identity** (record type, record id, field name, title) | rarely | needed for collaboration channels and AI context |
| Language / locale of the field | sometimes | needed for multilingual records |
| A drop or insert hook for host elements | sometimes | see `host-features.md` |

When identity is missing, derive it from the host's context API (a "current
record" hook) or from the surface's markup, document that dependency, and ask
the host to pass it (point 7).

## Lifecycle

- Value flows in on mount and on external changes (undo in the host, reload,
  language switch). Apply those with `setData()` only when the incoming value
  differs from the last value you emitted.
- Emit on change; debounce if the host re-renders on every keystroke.
- Destroy the editor when the surface unmounts; release shared resources
  (a shared AI context) by reference count.
- Several editors on one screen are normal. Initialize lazily if the host
  renders many at once.
