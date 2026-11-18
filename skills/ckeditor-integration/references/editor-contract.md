# 7. What the host should offer: an editor contract

Use this when the host has no documented editor slot, or when writing the
upstream issue for one. It is what a rich text editor integration needs from
any platform, written as a TypeScript sketch.

```ts
interface RichTextEditorProps {
  value: string                       // stored HTML
  onChange: (html: string) => void
  disabled?: boolean
  placeholder?: string
  width?: string | number
  height?: string | number
  maxCharacters?: number
  editorConfig?: Record<string, unknown>   // per-field options, editor-neutral or namespaced per editor
  surface: string                     // for example 'record-field' | 'page-region'

  // Usually missing today, needed for collaboration, AI and multilingual content
  field: {
    recordType: string
    recordId: string | number
    name: string                      // full path for nested fields
    title?: string
    language?: string
  }
  allowedHtml: {                      // the host sanitizer allowlist, so the editor can match it
    elements: string[]
    attributes: Record<string, string[]>
  }
}

interface RichTextEditorRef {
  onDrop: (payload: HostDragPayload) => void   // host elements dropped onto the editor
  focus?: () => void
}
```

```yaml
# Editor selection as host config, not registration order
host:
  rich_text:
    editor: ckeditor   # ckeditor | <other plugin> | default
```

Ask the host for:

1. **A documented slot** and this props/ref contract, covering every editing
   surface (fields, page regions, iframes).
2. **Editor selection as a config value**, plus registry priorities or a warning
   when two plugins claim the slot.
3. **Typed field identity** in the props.
4. **The sanitizer allowlist exposed** to the editor, or an extension point to
   extend it, so markup is never dropped silently.
5. **A runtime config channel** from backend to the editor (the host's app
   config), so integrations never bake values into builds.
6. **A panel or sidebar extension point** on every editing surface, for AI,
   comments and presence.
