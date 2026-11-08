# Runtime test plan (on the running stack)

Static state as of 2026-11-05: PHP lints clean, TypeScript type-check passes, production
frontend build committed under `public/studio/build/<build-id>/` with a valid
`entrypoints.json` (entries: `main`, `ckeditor_studio_bundle`, `exposeRemote`).
Nothing has been booted against Pimcore yet. These are the exact steps for tomorrow.

## 1. Bring the branch to the running stack

The work lives on branch `feat/ckeditor-studio-bundle`. Merge or check it out in the
main checkout that the Docker stack mounts.

## 2. Install the package (path repository)

The root `composer.json` already has the path repository and the
`simply007/ckeditor-studio-bundle: @dev` requirement. Only the lock/install step was
deliberately not run. Run it inside the container (host PHP lacks extensions):

```bash
docker compose exec php composer update simply007/ckeditor-studio-bundle
```

Expected: the package is symlinked into `vendor/simply007/ckeditor-studio-bundle`.
If the symlink does not resolve inside the container, the `packages/` dir is not
mounted; check `docker-compose.yaml` volumes (the skeleton mounts the project root,
so it should be fine).

## 3. Enable the bundle

```bash
docker compose exec php bin/console pimcore:bundle:enable CKEditorStudioBundle
docker compose exec php bin/console pimcore:bundle:list | grep -i ckeditor
```

Expected: `Simply007\CKEditorStudioBundle\CKEditorStudioBundle` enabled.
(`pimcore:bundle:enable` edits `config/bundles.php`; that edit happens on the live
stack, intentionally not in this branch.)

## 4. Publish assets and clear cache

```bash
docker compose exec php bin/console assets:install --symlink
docker compose exec php bin/console cache:clear
```

Expected: `public/bundles/ckeditorstudio/` exists and contains `studio/build/<build-id>/`.
Note the deploy quirk from commit 931a963: `public/bundles` is a mounted volume on
read-only deployments; locally this is a non-issue.

## 5. Check Studio

1. Open `/studio` (log in as admin).
2. Open a data object with a wysiwyg field, or a document with a wysiwyg editable.
3. Expected: a CKEditor classic toolbar (undo/redo, heading, bold, italic, underline,
   alignment, lists, blockquote, link, table, source editing) instead of the Quill one.
4. Browser console: no module federation load errors, no CKEditor license errors
   (`licenseKey: 'GPL'` is set in the component).

## 6. Known gotcha: override order vs quill-bundle

`pimcore/quill-bundle` is installed AND enabled in this skeleton, and it overrides the
same component (`componentConfig.wysiwyg.editor.name`). Both plugins call
`componentRegistry.override(...)` at startup; whichever runs last wins, and the order
follows entry point registration order, which is not guaranteed.

If Quill still renders:

```bash
# Option A (clean for the demo): disable the Quill bundle
# edit config/bundles.php: set PimcoreQuillBundle => ['all' => false] or remove the line
docker compose exec php bin/console cache:clear
```

Composer still requires `pimcore/quill-bundle` (platform dependency); only its bundle
registration is disabled. Document the outcome either way, it is a finding for the talk.

## 7. Functional checks (minimal viable)

- Type text, apply bold/heading, save the element, reload: HTML value persists.
- Paste HTML with `class`/`style`/`pimcore_id` attributes, save, reload: attributes
  survive (GeneralHtmlSupport config).
- Drag an image asset from the asset tree into the editor: an `img` with
  `pimcore_id`/`pimcore_type` and the thumbnail URL is inserted.
- Drag a document/object onto the editor with no text selected: a link with
  `pimcore_id`/`pimcore_type` is inserted. (With text selected, only `href` is applied;
  known demo limitation.)
- A field with `maxCharacters` shows a red border + tooltip when exceeded.

## 8. Blog demo (content model use case)

The branch also ships an app-level blog demo (added on request, outside the bundle):
`src/Command/InstallBlogDemoCommand.php`, `src/Controller/BlogController.php`,
`templates/blog/`. It shows the actual use case: a content model defined in the
database with a CKEditor-edited wysiwyg field, rendered on listing and detail pages.

```bash
docker compose exec php bin/console ckeditor-studio:install-blog-demo
```

Expected: class `BlogPost` (title, slug, publishDate, teaser, content wysiwyg) appears
in Studio under group Blog, folder `/blog` with two published sample posts.

1. Open `/blog` on the frontend: listing of the sample posts.
2. Click through to `/blog/hello-ckeditor`: detail page renders the wysiwyg HTML.
3. In Studio, edit the `content` field of a post with CKEditor, save and publish,
   reload the frontend page: the change shows up. That is the demo loop for the talk.

Notes: the command is idempotent (skips existing class/folder/posts). The class
creation API (`ClassDefinition` + `Layout\Panel` + `Data\Wysiwyg`) was verified against
vendor sources but not executed; if `save()` complains, create the class manually in
Studio with those five fields and rerun the command for the sample posts.

## 9. Frontend dev loop (optional, only if iterating)

```bash
cd packages/ckeditor-studio-bundle/assets/studio
npm install
npm run dev-server   # port 3033; Studio discovers it automatically
# or: npm run dev    # one-off development build into public/studio/build/
```

Do not run builds while a dev server for this package is running.

## 10. Runtime configuration (license key, toolbar)

Verified 2026-11-05 on the running stack:

```bash
docker compose exec php bin/console cache:clear
curl -sL http://localhost/pimcore-studio/ | grep -o 'data-app-config="[^"]*"'
# expected: ..."ckeditorStudio":{"licenseKey":null,"toolbar":[...],"premium":{...}}...

docker compose exec php bin/console debug:container --env-var=CKEDITOR_LICENSE_KEY
# expected: default "" (no key -> editor uses licenseKey 'GPL')
```

To test the gating with a key: set `CKEDITOR_LICENSE_KEY` in `.env.local`,
`cache:clear`, reload Studio, and check the editor console for license errors.
To test a toolbar preset: set `ckeditor_studio.toolbar` in
`config/packages/ckeditor_studio.yaml`, `cache:clear`, reload Studio. The premium
path (trial key, token endpoint, CKEditor AI chat round trip, real-time collaboration
sync between two tabs) was verified live on 2026-11-05; the walkthrough is in the
README, "Testing premium locally".

## Out of scope / unproven

- Not booted: bundle discovery, prepend config, entry point registration are
  statically mirrored from quill-bundle but unproven at runtime.
- `editorConfig` passed by Pimcore field definitions is Quill-shaped in 2026.x;
  CKEditor ignores unknown keys, no mapping implemented.
- Shared translations and the document editor iframe entry point tag are wired but untested.
- No premium features, no config UI, no translations shipped.
