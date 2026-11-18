# 4. Integration with host features

The editor is "done" only when every host feature that touched rich text with
the previous editor still works. Inventory them from the previous editor's
source and the host's backend, then test each.

## Host references in markup

Hosts mark internal links and embedded assets with their own attributes or
URL schemes (for example `<a href="..." data-entity-id="12" data-entity-type="page">`,
custom `pimcore_id` / `pimcore_type`, `{{link:12}}` placeholders). The backend
uses them for link rewriting, renames and moves, dependency tracking, "where is
this used", publishing checks and deletion warnings.

- Preserve them on load (GHS rules or a converter, see `data-and-collaboration.md`).
- Produce them on insert (drop, link picker, image picker).
- Applying a link to a **selection** through the standard link command sets
  only `href`. Carrying extra attributes onto a selection needs a model
  attribute or a converter; plan it, or document the limitation.

## Drag and drop from host trees

Host UIs let users drag assets, pages and records from a tree or a media
library. These arrive as host payloads, not as HTML. Handle them in the
host's drop hook (or a DOM drop listener) and insert through the editor model:

```js
// Insert host-generated HTML at the selection, through the data pipeline,
// so GHS rules and converters apply.
const view = editor.data.processor.toView(html);
editor.model.insertContent(editor.data.toModel(view));
```

Escape every attribute value from the payload. Route images through the
host's thumbnail or image service when the previous editor did (size limits,
formats the browser cannot show).

## Pickers and uploads

- Image upload: the host's media library API through a custom upload adapter,
  so files land in the host DAM with its permissions, not in a side folder.
- Link and image pickers: a toolbar button that opens the host picker and
  inserts as above.

## Delivery and other consumers

Check every consumer of the stored HTML: frontend templates, headless and
GraphQL APIs, search indexing, exports, translation workflows, emails, PDF
rendering. Each must handle the markup CKEditor now produces (figures,
tables, alignment classes). Ship CKEditor's content styles to the frontend
when published content should match the editing view.

## Admin UI fit

- Host design system CSS (z-index of modals and popovers, global resets) can
  hide CKEditor balloons and dropdowns; test them inside the host's dialogs.
- Theme CKEditor through its CSS variables to match the host admin, dark mode
  included.
- Panels (AI chat, comments, presence list) need a place in the host layout;
  if the host has no sidebar extension point for every surface, dock your own
  panel and make the content area give way instead of covering it.
- Editor UI language follows the host admin language.
