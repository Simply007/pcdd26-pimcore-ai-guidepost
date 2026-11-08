# CKEditor Studio Bundle (demo)

CKEditor rich text editor mounted as the wysiwyg editor in Pimcore Studio. Built for
the talk "AI Best Practices for Your Product" at Pimcore Community Dev Days 2026, as
the missing CKEditor counterpart to `pimcore/quill-bundle`, whose 2026.x architecture
this bundle mirrors.

Status: the core integration is runtime-tested end to end on the demo stack (see
`TESTING.md`); the runtime configuration described below is build-tested and
statically verified. The premium feature path (CKEditor AI, real-time collaboration,
with the bundle's own Cloud Services token endpoint) is loaded on demand behind
dynamic imports and was verified live in Studio with a trial key (see "Testing
premium locally"). Experiment log on the agent skills used to build it: see
`SKILL-FINDINGS.md`.

## What it does

- Replaces the Quill editor with CKEditor for every wysiwyg surface in Pimcore
  Studio: data object wysiwyg fields and document wysiwyg editables (both render
  through the same registry component).
- Classic editor, open source build (npm packages `ckeditor5` +
  `@ckeditor/ckeditor5-react`), with license-key gating for premium use (see below).
- Asset/document/object drag and drop inserts images and links with `pimcore_id` /
  `pimcore_type` attributes; `GeneralHtmlSupport` keeps those attributes (plus
  class/id/style) in the data.

## How it works

Pimcore Studio is a module-federation host. This bundle contributes a remote:

1. The Symfony bundle (`Chrastina\CKEditorStudioBundle`) registers a
   `WebpackEntryPointProvider` (tag `pimcore_studio_ui.webpack_entry_point_provider`,
   plus the document editor iframe tag) that points Studio at the committed rsbuild
   output in `public/studio/build/<build-id>/entrypoints.json`.
2. The frontend (`assets/studio/`, rsbuild + module federation, dev server port 3033)
   exposes a Studio plugin. On startup it grabs the `ComponentRegistry` from the
   Studio SDK container and calls
   `componentRegistry.override({ component, name: componentConfig.wysiwyg.editor.name })`:
   the single `wysiwyg.editor` component slot now renders CKEditor.
3. The replacement component implements Studio's `WysiwygProps` / `WysiwygEditorRef`
   contract (value/onChange, drag and drop hook, maxCharacters), so Studio core treats
   it exactly like the built-in editor.
4. Runtime configuration travels the other way: a `StudioAppConfigProvider` (tag
   `pimcore_studio_ui.app_config_provider`) merges a `ckeditorStudio` key into the
   `data-app-config` JSON on the Studio shell page, and the editor reads it back
   through `appConfig` from `@pimcore/studio-ui-bundle/app`. Config changes therefore
   need only a `cache:clear`, never a frontend rebuild.

## Install and enable

In this repository everything is pre-wired (path repository in the root
`composer.json`, bundle active in `config/bundles.php`). From scratch:

```bash
# 1. Composer (path repository route; see "Extracting this bundle" for standalone)
composer require chrastina/ckeditor-studio-bundle:@dev

# 2. Enable the bundle (writes config/bundles.php)
docker compose exec php bin/console pimcore:bundle:enable CKEditorStudioBundle

# 3. Publish assets and clear cache
docker compose exec php bin/console assets:install --symlink
docker compose exec php bin/console cache:clear
```

Other editor bundles (for example PimcoreQuillBundle) can stay enabled; pick the
editor with `ckeditor_studio.editor` (see "Editor selection") and open Studio: wysiwyg
fields render the selected editor.

## Configuration

All configuration lives under the `ckeditor_studio` key, for example in
`config/packages/ckeditor_studio.yaml`. Everything is optional; with no config at all
the editor runs the open source feature set with the default toolbar.

### Editor selection

Several wysiwyg editor bundles can stay enabled side by side; one value decides which
editor Studio uses:

```yaml
ckeditor_studio:
  editor: ckeditor   # ckeditor (default) | quill | default
```

- `ckeditor`: this bundle's editor.
- `quill`: the editor another bundle registered (PimcoreQuillBundle); when no other
  editor bundle is enabled, it falls back to CKEditor with a console warning.
- `default`: Studio's built-in editor.

Switching needs only `cache:clear` (the value reaches the browser through the app
config, no rebuild). How it works: Studio's component registry has no arbitration
(the last `override()` of the `wysiwyg.editor` slot wins), but its order is
deterministic: Studio registers its built-in editor first, then plugin modules
initialize in ascending plugin `priority`. This bundle ships two plugins, a probe at
priority -1000 that records Studio's built-in editor before any editor bundle runs,
and the selector at priority 1000 that runs after every other editor bundle and
applies the configured choice. Applies to data object fields and document editables
(the editmode iframe bootstraps plugins the same way).

### License key gating

- No key configured (default): the editor runs with `licenseKey: 'GPL'` and the
  current free feature set. The "Powered by CKEditor" logo is part of that deal.
- Key configured: it is passed to CKEditor as `licenseKey` and unlocks the premium
  gates below. The simplest route is the environment variable the config defaults to:

```bash
# .env.local (never commit real keys)
CKEDITOR_LICENSE_KEY=your-key-from-portal.ckeditor.com
```

or explicitly in yaml:

```yaml
ckeditor_studio:
  license_key: '%env(CKEDITOR_LICENSE_KEY)%'
```

The key reaches the browser through the Studio shell page. That is how CKEditor
licensing works everywhere (the key is validated client side and is not a secret in
the server sense), but keep genuinely secret values, such as an AI provider API key,
on the server.

### Toolbar

The toolbar is a plain list of CKEditor toolbar items, in the spirit of TYPO3 RTE
presets or Drupal editor config. The default preset is:

```yaml
ckeditor_studio:
  toolbar:
    - undo
    - redo
    - '|'
    - heading
    - '|'
    - bold
    - italic
    - underline
    - '|'
    - alignment
    - '|'
    - bulletedList
    - numberedList
    - outdent
    - indent
    - '|'
    - blockQuote
    - link
    - insertTable
    - '|'
    - sourceEditing
    - fullscreen
```

Change it by overriding the whole list; `'|'` is a separator. A minimal preset:

```yaml
ckeditor_studio:
  toolbar: ['undo', 'redo', '|', 'bold', 'italic', 'link']
```

Items must be backed by a plugin the frontend build ships (currently: Essentials,
Paragraph, Heading, Bold, Italic, Underline, Alignment, Link, List, Indent,
BlockQuote, Table, Image, ImageResize, SourceEditing, GeneralHtmlSupport, Fullscreen).
`fullscreen` (free, part of the open source package) expands the editor over the
whole Studio window in a page-style layout and returns cleanly. With the premium
gates on, fullscreen carries the CKEditor AI panel and the connected users list
along, so AI keeps working there (verified live in Studio).

Data object fields without a configured `height` are fitted to Studio's panel (at most
70% of the viewport), so long content scrolls inside the editor and the toolbar stays
visible; a `height` on the field definition wins. Document editables always grow with their
content, and a template `height` (`pimcore_wysiwyg('intro', { height: 300 })`) is
ignored: a capped field inside the editmode iframe would catch the mouse wheel and stop
the page from scrolling while the pointer is over its text. Unknown
items are skipped by CKEditor with a console warning, nothing breaks. Adding an item
that needs a new plugin means adding the plugin in
`assets/studio/js/src/modules/ckeditor-editor/editor.tsx` and rebuilding. The AI
gate appends its own toolbar buttons (`toggleAi`, `aiQuickActions`); other AI items
(`ask-ai`, `improve-writing`) can be added here once the gate is on.

Per-field tweaks remain possible: whatever a field definition or
`pimcore_studio_ui.wysiwyg.default_editor_config` passes as `editorConfig` is spread
over the bundle config last, so genuine CKEditor options set there win (note that
Pimcore 2026.x writes that config Quill-shaped; CKEditor ignores unknown keys).

### Inline editor for document editables

Document editables use the classic editor (toolbar above a framed editing area) by
default. A template can switch a single editable to CKEditor's inline editor, which
edits the text in place in the page layout and shows a floating toolbar while the
field is focused:

```twig
{{ pimcore_wysiwyg('heroSubline', { editorType: 'inline', placeholder: 'One or two sentences' }) }}
```

The text keeps the page's own typography; a light frame marks the field on hover
and while focused. The floating toolbar is not grouped into an overflow menu, so
narrow columns still show every button. `fullscreen` is left out of the inline
toolbar (CKEditor's Fullscreen supports the classic and decoupled editor types only).
CKEditor AI, the shared AI Context and real-time collaboration work the same way.
The option applies to document editables only; data object fields always use the
classic editor. The landing page document type (`templates/landing/landing.html.twig`)
uses it for all its rich text fields.

### Premium feature gates

Two premium features are wired behind config gates: CKEditor AI and real-time
collaboration. The premium code (`ckeditor5-premium-features`, pinned to the exact
same version as `ckeditor5`) is loaded by the browser through a dynamic import ONLY
when both a license key is configured and at least one gate is enabled. With no key,
or with the gates off, the editor initializes exactly as the plain open source build,
no premium chunk is even fetched (the premium code and CSS live in their own async
chunks), and Studio's Content Security Policy stays untouched. A gate whose
prerequisites are missing logs a console warning and keeps the free feature set, so
a misconfigured instance never breaks. The wiring lives in
`assets/studio/js/src/modules/ckeditor-editor/premium.ts`.

This repository commits `config/packages/ckeditor_studio.yaml` with both gates
driven by environment variables that default to off:

```yaml
parameters:
  env(CKEDITOR_PREMIUM_AI): '0'
  env(CKEDITOR_PREMIUM_RTC): '0'

ckeditor_studio:
  premium:
    ai:
      enabled: '%env(bool:CKEDITOR_PREMIUM_AI)%'
      # Where the AI UI renders: sidebar (default, one docked panel per element
      # next to the content) or overlay (floats over the page).
      # container: sidebar
      # Optional raw CKEditor "ai" configuration, passed to the shared Context
      # (serviceUrl, chat, ...).
      # config: {}
    realtime_collaboration:
      enabled: '%env(bool:CKEDITOR_PREMIUM_RTC)%'
      # Leave both null to use the bundle's own token endpoint and
      # CKEDITOR_CS_WEBSOCKET_URL; set them to point at your own services.
      # token_url: ~
      # web_socket_url: ~
```

The full set of environment variables the bundle reads (all optional, all empty by
default; set them in `.env.local` or your secret store, never in committed files):

| Variable | Used for |
|---|---|
| `CKEDITOR_LICENSE_KEY` | `licenseKey` in the browser; empty means `'GPL'` |
| `CKEDITOR_CS_ENVIRONMENT_ID` | Cloud Services environment id, the token `aud` claim (server side) |
| `CKEDITOR_CS_ACCESS_KEY` | Cloud Services access key, signs the tokens (server side only, never sent to the browser) |
| `CKEDITOR_CS_WEBSOCKET_URL` | Cloud Services WebSocket URL for real-time collaboration |
| `CKEDITOR_PREMIUM_AI` | gate: CKEditor AI (`1` / `0`, via the yaml above) |
| `CKEDITOR_PREMIUM_RTC` | gate: real-time collaboration (`1` / `0`, via the yaml above) |

What turning each gate on does (with a license key configured):

- `premium.ai.enabled` turns on CKEditor AI with ONE chat per Pimcore element that
  sees every wysiwyg field of that element (see "One AI chat per element" below),
  and appends the `toggleAi` and `aiQuickActions` buttons to the toolbar (they cannot
  sit in the yaml toolbar, because the free path has no plugin behind them). The AI
  service authenticates with the Cloud Services token, so it needs the token endpoint
  even when the collaboration gate is off.
- `premium.realtime_collaboration.enabled` loads `RealTimeCollaborativeEditing`
  (remote carets and selections included) plus a presence list rendered above the
  editor, with `cloudServices` built from the token endpoint and the WebSocket URL.
  It also sets `sourceEditing.allowCollaborationFeatures: true`, because the free
  SourceEditing plugin otherwise refuses to start next to real-time collaboration.
  With collaboration active, the document state lives in Cloud Services and the
  editor stops applying external value resets; changes still flow back into the
  Pimcore form (the object shows as modified and saves normally).

Collaboration channel id: derived per field as
`pimcore-<elementType>-<elementId>-<fieldName>`, for example
`pimcore-data-object-3-content` or `pimcore-document-1-intro`. Data object fields take
the element from Studio's element context and the field from `combinedFieldName`
(nested fields such as localized fields stay unique). Document editables render in
the editmode iframe, where Studio passes neither: the element id comes from the
iframe page (`window.pimcore_document_id`) and the field from the editable's
`#pimcore_editable_<name>` container. An explicit per-field
`editorConfig.collaboration.channelId` always wins.

### One AI chat per element (shared Context)

With the AI gate on, all editors of one Pimcore element share one CKEditor
`Context` (CKEditor's multi-editor AI support, stable since v48.3):

- The Context (key and channel `pimcore-<elementType>-<elementId>`) carries the AI
  feature plugins (`AIChat`, `AIChatHistory`, `AIChatShortcuts`, `AIReviewMode`,
  `AITranslate`), `CloudServices`, the `ai` configuration (including
  `premium.ai.config`) and, with the RTC gate on too, one shared `PresenceList`.
- Each editor keeps `AIEditorIntegration` + `AIQuickActions` (+ RTC) and describes
  itself through `root.title` / `root.description` (field title, or the humanized
  editable name such as "Found resources"), which is how the AI tells fields apart
  and puts an edit in the right one.
- The Context is created by the first editor of the element, reference counted, and
  destroyed shortly after the last one unmounts (element tab closed). The free path
  and the AI-off path create no Context.

AI panel (`premium.ai.container`): `sidebar` (default) renders the chat into one
panel per element, docked on the right of the scroll container that holds the
element's editors (the editmode iframe for documents, Studio's edit panel for data
objects); that container gets matching right padding, so the content moves aside
instead of being covered. The panel is 32% of the available width, clamped to 300
to 440px, hides with the AI toggle button, follows Studio's tab switching, and the
editor's fullscreen mode takes the chat along and hands it back on exit. `overlay`
restores CKEditor's floating panel. Studio has a sidebar extension point for
documents only (`Document/Editor/Sidebar/*SidebarManager`) and none for data
objects, and document editables live in an iframe while that sidebar lives in the
parent window, so the bundle docks its own panel instead.

### Cloud Services token endpoint

The bundle ships its own token endpoint:
`GET /pimcore-studio/api/ckeditor-studio/cs-token` (route `ckeditor_studio_cs_token`).

- Lives under `/pimcore-studio/api`, so the Studio firewall and its
  `ROLE_PIMCORE_USER` access rule apply: anonymous requests get `401`, and the editor
  fetches the token with the Studio session cookie (same origin).
- Returns a plain-text HS256 JWT signed with `CKEDITOR_CS_ACCESS_KEY`: `aud` =
  `CKEDITOR_CS_ENVIRONMENT_ID`, `sub` = the Pimcore user id, `user.name` (first and
  last name, falling back to the username) and `user.email`, `iat`/`exp` (1 hour),
  `auth.collaboration` = `{"*": {"role": "writer"}}` (demo scope: every channel), and
  `auth.ai.permissions` = the "end user" profile from the CKEditor AI docs
  (`ai:conversations:read`, `ai:conversations:write`, `ai:actions:*`, `ai:reviews:*`,
  `ai:models:agent`). Narrow both for production.
- Returns `503` when the Cloud Services variables are not set. When they are set and
  `premium.realtime_collaboration.token_url` is null, the editor's `tokenUrl`
  defaults to this route automatically.
- The access key never leaves the server; only the token URL reaches the browser.

Studio's CSP: Pimcore Studio sends a strict `connect-src`, which blocks the browser's
calls to Cloud Services (AI over HTTPS, collaboration over a WebSocket) and the
license usage endpoint. A bundle `CspEvent` subscriber adds `https://*.cke-cs.com`,
`wss://*.cke-cs.com`, the `cke-cs-eu.com` equivalents and
`https://proxy-event.ckeditor.com` to `connect-src`, only while a license key is set
and a gate is on.

### Testing premium locally

A free trial from [portal.ckeditor.com](https://portal.ckeditor.com/) (no credit
card) provides everything the gates need: a trial license key covering the premium
features, and a Cloud Services environment (environment id, an access key you create
in the portal, the WebSocket URL). Keys are channel-scoped; this bundle self-hosts
via npm, so use a key valid for self-hosted use. Then:

1. Put the values in `.env.local` (gitignored, never commit them):

   ```bash
   CKEDITOR_LICENSE_KEY=<license key>
   CKEDITOR_CS_ENVIRONMENT_ID=<environment id>
   CKEDITOR_CS_ACCESS_KEY=<access key>
   CKEDITOR_CS_WEBSOCKET_URL=<wss://...cke-cs.com/ws>
   CKEDITOR_PREMIUM_AI=1
   CKEDITOR_PREMIUM_RTC=1
   ```

2. `docker compose exec php bin/console cache:clear`. No frontend rebuild and no yaml
   change: the gates and URLs are runtime configuration read from the Studio shell page.
3. Open a data object with a wysiwyg field in Studio (in this repo: `/blog/hello-ckeditor`,
   field Content).

What to expect (verified live on 2026-11-05 with a long-term trial, Chrome):

- The console shows CKEditor's trial license notice and no license error.
- The premium chunks load only now; the token endpoint returns `200` for the logged-in
  user.
- The AI panel is docked next to the content (not over it); the toolbar carries the
  AI and AI Quick Actions buttons. A chat message such as "Summarize this document in one
  sentence." returns an answer about the current document.
- The presence list above the toolbar shows the current user (the Pimcore user from
  the token's `sub`), the channel id is `pimcore-data-object-3-content`, and an edit
  made in a second tab on the same object appears live in the first one. Two tabs of
  the SAME Pimcore user show one avatar; log in as a second Pimcore user to see two.
- On a document with several editables (the Home page has five), the chat lists all
  of them by title and an edit request that names one field ("add a sentence to the
  Intro that mentions the Reference points") lands in that field only.
- Set both gates back to `0` (or remove the key) and `cache:clear`: no premium chunk
  loads and Studio's CSP is back to its original `connect-src`.

Backend prerequisites beyond the key, with pointers:

- CKEditor AI needs the AI service on your Cloud Services environment (trial
  environments include it) and a token with `auth.ai.permissions`; see the
  [CKEditor AI integration guide](https://ckeditor.com/docs/ckeditor5/latest/features/ai/ckeditor-ai-integration.html),
  [CKEditor AI authentication](https://ckeditor.com/docs/cs/latest/guides/ckeditor-ai/authentication.html)
  and [permissions](https://ckeditor.com/docs/cs/latest/guides/ckeditor-ai/permissions.html).
  The default AI service URL is the US region; for an EU environment set
  `premium.ai.config.serviceUrl` to `https://ai.cke-cs-eu.com/v1`.
- Real-time collaboration needs the Cloud Services environment and a token with
  `auth.collaboration`; see the [token endpoint guide](https://ckeditor.com/docs/cs/latest/developer-resources/security/token-endpoint.html)
  and the [collaboration quick start](https://ckeditor.com/docs/cs/latest/guides/collaboration/quick-start.html).

## Limitations

- Editor selection relies on plugin priorities (this bundle at -1000 and 1000). An
  editor bundle that also uses an extreme priority could still race it; Studio itself
  has no slot arbitration (see
  [issue #2](https://github.com/Simply007/pcdd26-pimcore-ai-guidepost/issues/2) for the
  upstream angle). Per-class or per-field editor choice is not implemented.
- Document editables are identified from the editmode iframe DOM
  (`#pimcore_editable_<name>`, `window.pimcore_document_id`) because Studio core passes
  neither; a Pimcore change to that markup would need a matching change here.
- The docked AI panel was checked live on a wide screen and at laptop size
  (1384x797, panel 300px). In document editmode it is fixed inside the editmode
  iframe and narrows the page content without changing the viewport width, so a
  template that switches layout with viewport media queries keeps its wide layout and
  overflows under the panel. Prefer layouts that adapt to the available width
  (`repeat(auto-fit, minmax(...))`, flex wrap, container queries).
- The token endpoint grants every Studio user writer access to every channel and the
  AI end-user profile. Fine for a demo; production should map Pimcore permissions to
  per-channel roles.
- Pimcore's per-field `editorConfig` is Quill-shaped in 2026.x; no mapping to
  CKEditor options is implemented (pass-through only).
- Dropping an element onto selected text applies only `href`, not the `pimcore_id` /
  `pimcore_type` attributes.
- No translations shipped; no automated tests.

## Layout

```
composer.json                PHP package (type: pimcore-bundle, PSR-4 Chrastina\CKEditorStudioBundle)
src/                         Bundle class, configuration, app config provider, entry point provider,
                             Cloud Services token generator + controller, premium CSP subscriber
config/studio_ui.yaml        Tags the providers for Studio UI (incl. document editor iframe)
config/pimcore/routing.yaml  Token endpoint route (auto-loaded by Pimcore's bundle routing loader)
assets/studio/               TypeScript frontend (rsbuild + module federation, port 3033)
public/studio/build/<id>/    Committed frontend build (entrypoints.json consumed by PHP)
```

## Extracting this bundle

The bundle is self-contained under `packages/ckeditor-studio-bundle` and extraction
to its own repository is tracked as
[issue #3](https://github.com/Simply007/pcdd26-pimcore-ai-guidepost/issues/3). A
standalone install needs:

- Distribution: copy this directory to its own repository; `composer.json` already
  carries the metadata (name, description, license, autoload, `type: pimcore-bundle`,
  `extra.pimcore.bundles`). Consumers then either add a VCS/path repository entry and
  `composer require chrastina/ckeditor-studio-bundle`, or the package gets published
  to Packagist and the repository entry disappears.
- Frontend build: the committed build under `public/studio/build/<build-id>/` ships
  with the package, so consumers need no Node toolchain. Maintainers rebuild with
  `npm ci && npm run build` in `assets/studio/` and commit the output. (Pimcore's own
  bundles ship a zip via `BuildArchiveProviderInterface` instead; switching to that
  route is optional polish.)
- Studio registration: nothing manual. `extra.pimcore.bundles` makes
  `pimcore:bundle:enable CKEditorStudioBundle` work, the prepended `studio_ui.yaml`
  registers the entry point and app config providers only when
  `pimcore_studio_ui` is present, and `assets:install` publishes the build.
- Versioning: pin `pimcore/studio-ui-bundle` (composer) and
  `@pimcore/studio-ui-bundle` (npm) to the same minor line; the frontend consumes
  Studio's SDK types at build time and its module federation remote at runtime.
