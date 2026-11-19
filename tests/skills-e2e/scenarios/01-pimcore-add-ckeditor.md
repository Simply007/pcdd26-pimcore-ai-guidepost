# 01: Make CKEditor the rich text editor of this Pimcore project

## Starting state (set by `bin/skills-e2e prepare`)

- `simply007/ckeditor-studio-bundle` removed from `composer.json` and
  `config/bundles.php`, cache cleared. Studio shows its default editor.
- `config/packages/ckeditor_studio.yaml` removed.
- Skills present: `ckeditor-integration` (this repo), the `ckeditor` skills
  (`.agents/skills/`), the Pimcore skills. `ckeditor-pimcore` is NOT present
  (it ships inside the removed bundle and appears once the bundle is
  installed).

## Prompt

```
Our content editors want CKEditor as the rich text editor in Pimcore Studio,
for data object wysiwyg fields and document editables. Set it up in this
project. Keep the free (open source) feature set for now, but make it
possible to switch on a license key later without a rebuild. Verify it works.
```

## Pass criteria (scripted in `bin/skills-e2e check`)

1. `simply007/ckeditor-studio-bundle` is required in `composer.json` (an
   existing integration was reused, not rewritten).
2. The bundle is enabled and its build is published.
3. `/pimcore-studio/` carries `ckeditorStudio` in `data-app-config` with
   `editor: ckeditor` and no license key.
4. The license key is read from the environment (`CKEDITOR_LICENSE_KEY`), and
   no tracked file contains a key value.
5. `bin/demo-preflight` passes the container, Studio, editor selection and
   token endpoint checks.
6. The transcript shows the agent read `ckeditor-integration` and, after the
   install, `ckeditor-pimcore`.

## Watch for (manual review of the transcript)

- Did it check for an existing integration before writing code?
- Did it warn about the sanitizer dropping `code` / `pre`?
- Did it verify in the running app instead of declaring done?
