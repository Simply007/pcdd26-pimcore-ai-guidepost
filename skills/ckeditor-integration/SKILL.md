---
name: ckeditor-integration
description: >-
  Use when integrating CKEditor 5 into a host platform rather than a single
  app: shipping it as the rich text / WYSIWYG editor of a CMS, PIM, DAM,
  admin framework, low-code tool or SaaS backend, as a plugin, bundle,
  extension or module of that platform, or replacing the platform's built-in
  editor (Quill, TinyMCE, Draft.js, a textarea). Also when content breaks at
  the platform boundary: markup stripped on save, internal links or asset
  references lost, records marked dirty on open, collaboration channels
  colliding, premium code loading without a key. NOT for adding CKEditor to
  one app (use `ckeditor`), the Cloud Services editor bundle (use
  `ckeditor-bundle`), or version upgrades (use `ckeditor-update`).
license: MIT
allowed-tools:
  - Read
  - Write
  - Edit
  - Bash
  - Glob
  - Grep
metadata:
  author: Ondřej Chrastina
  version: 0.1.0
  complements: ckeditor/skills
  source: Simply007/pcdd26-pimcore-ai-guidepost
---

# CKEditor in a host platform

Getting CKEditor running is the `ckeditor` skill's job. This skill covers the
other half: the **contract between the editor and the system that hosts it**.
Most integration bugs live there, not in the editor config, and they fail
silently (markup gone after save, links that no longer resolve, two users
overwriting each other).

**Route editor-side work out.** Install method, editor type, plugins, toolbar
items, CSS, license key semantics, premium feature setup: use the `ckeditor`
skill and the live CKEditor docs. Do not restate them here.

## First: does an integration already exist?

Before designing, search for an official or community CKEditor integration for
this host (CKEditor publishes several, and hosts often list editor plugins).
Check that it supports the host's exact version, then evaluate it against the
seven points below. Configuring or contributing to it beats a new one.

## Work from the host's source, not assumptions

Every host differs in exactly the places below. Before designing, read the
host's current editor integration (the built-in one, or an existing editor
plugin such as a Quill bundle): it is the blueprint for the slot, the props,
the stored markup and the host features. Write down what it does for each of
the seven points, then match it.

**Two roles, two orders:**

- **You write a plugin for someone else's host:** work through points 1 to 6,
  and turn every gap you hit into an upstream request (point 7).
- **You own the host:** start with point 7. Implement the editor contract in
  the host, move the built-in editor onto the slot as the default entry, then
  build CKEditor against that contract like any other plugin.

## The seven integration points

Answer all seven before writing code. Each has a detail file.

| # | Question | Typical silent failure | Read |
|---|---|---|---|
| 1 | **Where does the editor plug in?** Slot, hook or registry; how one editor is selected; what the host passes in | Two editor plugins fight, last one wins at random; field identity missing | `references/editor-slot.md` |
| 2 | **Where does the data live?** Host field and revisions, plus what CKEditor adds (Cloud Services documents, comments, suggestions) | Two sources of truth under collaboration | `references/data-and-collaboration.md` |
| 3 | **How is it stored?** HTML as the contract, the host's sanitizer, round trips | Host sanitizer strips `code`/`pre`/attributes on save, no error | `references/data-and-collaboration.md` |
| 4 | **Does it work with host features?** Assets/DAM, internal links, drag and drop, delivery APIs, search | Host reference attributes dropped, dependency tracking breaks | `references/host-features.md` |
| 5 | **How is it configured?** Host config, runtime injection, license key, premium gates, token endpoint, CSP | Key baked into the build; premium chunk loaded for free users | `references/configuration.md` |
| 6 | **How is it shipped?** Host package format, committed build vs build on install, licenses | Package installs but the editor never loads | `references/configuration.md` |
| 7 | **What should the host offer?** A documented editor contract | Integration relies on undocumented internals | `references/editor-contract.md` |

## First version under time pressure

Do now: the slot (point 1), the stored format and any migration (point 3),
no value echo, assets and links (point 4), runtime config on the free path
(point 5), the round trip on real content. Defer, and say so: collaboration,
comments, AI, token endpoint, CSP, a shared AI context. The free path must
not depend on any of them.

## Rules that hold on every host

- **Mirror the previous editor's stored markup.** Content already in the
  database was written by it. CKEditor must load it unchanged and write what
  the host's backend expects (see points 3 and 4).
- **Never echo your own value back.** Emitting `onChange` for a value the host
  just passed in marks records as modified on open and loops. Track the last
  emitted HTML; apply only external changes.
- **Configuration is runtime, not build time.** Keys, toolbars and gates come
  from host config at page load. A rebuild per config change, or a key inside a
  committed build, is a design bug.
- **The free path stays free.** No license key: `licenseKey: 'GPL'`, no premium
  import, no premium request, unchanged CSP. Premium code loads through a
  dynamic import only when a key and a gate are both set.
- **Secrets stay on the server.** The license key reaches the browser by design;
  the Cloud Services access key and AI provider keys never do. Tokens come from
  a host endpoint behind the host's own auth.
- **Pin and follow releases.** Pin `ckeditor5` (and `ckeditor5-premium-features`
  at the exact same version) per integration release, and the host SDK per host
  line. Upgrades: the `ckeditor-update` skill.
- **Verify in the running host**, then use `references/checklist.md`.

## Worked example

The Pimcore Studio integration answers each point for one concrete host. It is
a separate skill, `ckeditor-pimcore`, shipped inside the Composer package
`simply007/ckeditor-studio-bundle` (source:
https://github.com/Simply007/pimcore-studio-ckeditor-bundle); the reference
installation is https://github.com/Simply007/pcdd26-pimcore-ai-guidepost. If
the skill is not installed, the bundle README and source are the same
reference. It shows:
Skip it unless you work on Pimcore or need a reference implementation of
the premium path. It shows a registry slot with priority-based selection, an app config provider for
runtime settings, `pimcore_id` / `pimcore_type` references, an iframe editing
surface, per-element AI context. Read it when you need a reference
implementation.
