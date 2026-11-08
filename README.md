# Pimcore Project Skeleton 

> **Looking for the PCDD 2026 "AI Best Practices" guidepost?** This repo doubles as the demo
> project for that talk. The skeleton docs below are the stock `pimcore/skeleton` instructions —
> for the actual AI-tooling resource guide (what's in here, what's verified, what's still a gap),
> see **[AGENTS.md](AGENTS.md)**.

This skeleton should be used by experienced Pimcore developers for starting a new project from the ground up. 
If you are new to Pimcore, it's better to start with our demo package, listed below 😉

## Getting started
```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/skeleton --no-scripts my-project
cd ./my-project
./vendor/bin/pimcore-install --install-profile='App\Installer\SkeletonProfile'
```

- Point your virtual host to `my-project/public`
- [Only for Apache] Create `my-project/public/.htaccess` according to https://pimcore.com/docs/platform/Pimcore/Installation_and_Upgrade/System_Setup_and_Hosting/Apache_Configuration/ 
- Open https://your-host/pimcore-studio in your browser
- Done! 😎

## Docker

You can also use Docker to set up a new Pimcore Installation.
You don't need to have a PHP environment with composer installed.

### Prerequisites

* Your user must be allowed to run docker commands (directly or via sudo).
* You must have docker compose installed.
* Your user must be allowed to change file permissions (directly or via sudo).

### Follow these steps
1. Initialize the skeleton project using the `pimcore/pimcore` image
``docker run -u `id -u`:`id -g` --rm -v `pwd`:/var/www/html pimcore/pimcore:php8.4-latest composer create-project pimcore/skeleton --no-scripts my-project``

2. Go to your new project
`cd my-project/`

3. Part of the new project is a docker compose file that already defines every required service
   (PHP, Nginx, MariaDB, Redis, RabbitMQ, OpenSearch, Mercure, and a Supervisord service that runs
   the messenger workers). You do not need to add any services yourself.
    * Run `sed -i "s|user: '1000:1000'|user: '$(id -u):$(id -g)'|g" docker-compose.yaml` to set the correct user id and group id.
    * Start the services with `docker compose up -d`

4. Install pimcore and initialize the DB
    `docker compose exec php vendor/bin/pimcore-install --install-profile='App\Installer\SkeletonProfile'`
    * The committed `.env` already provides the database, OpenSearch, RabbitMQ, Mercure, and admin
      values, so the installer does not re-prompt for them. It only asks for the **product key**
      (product registration is mandatory — see <https://license.pimcore.com/register>).
    * The default admin login is `admin` / `admin` (from `.env`). Change the password after first login.
    * The installer also builds the search index automatically (a GenericDataIndex post-install command).
    * This can take a while.

5. Run codeception tests:
   * `docker compose run --user=root --rm test-php chown -R $(id -u):$(id -g) var/ public/var/`
   * `docker compose run --rm test-php vendor/bin/pimcore-install -n`
   * `docker compose run --rm test-php vendor/bin/codecept run -vv`

6. :heavy_check_mark: DONE - You can now visit your pimcore instance:
    * The frontend: <http://localhost>
    * Pimcore Studio, using the credentials from `.env` (default `admin` / `admin`):
      <http://localhost/pimcore-studio>

## CKEditor in Pimcore Studio

This skeleton ships a working CKEditor integration for Pimcore Studio:
[`packages/ckeditor-studio-bundle`](packages/ckeditor-studio-bundle/README.md)
(namespace `Chrastina\CKEditorStudioBundle`), built as the CKEditor counterpart to
`pimcore/quill-bundle` for the PCDD 2026 talk. It registers a module federation
remote that overrides Studio's `wysiwyg.editor` component slot, so data object
wysiwyg fields and document editables render CKEditor.

- License key: optional. Without one the editor runs the open source feature set
  (`licenseKey: 'GPL'`); set `CKEDITOR_LICENSE_KEY` to use a commercial key and the
  premium gates (CKEditor AI, real-time collaboration), loaded on demand only
  when a key is configured and a gate is enabled.
- Toolbar: configurable via `ckeditor_studio.toolbar` yaml, with a sensible default
  preset.
- Editor selection: both PimcoreQuillBundle and this bundle stay enabled;
  `ckeditor_studio.editor` (`ckeditor` | `quill` | `default`) in
  `config/packages/ckeditor_studio.yaml` picks the editor, with CKEditor as the
  fallback when Quill is not available.

Setup, configuration and limitations:
[bundle README](packages/ckeditor-studio-bundle/README.md).

Before a demo, run `bin/demo-preflight`: it checks the containers, the demo pages,
the published editor build, the editor selection and which premium gates are on,
printing states only, never key values.

## Pimcore Platform Version
By default, Pimcore Platform Version is added as a dependency which ensures installation of compatible and in combination 
with each other tested versions of additional Pimcore modules. More information about the Platform Version can be found in the 
[Platform Version docs](https://github.com/pimcore/platform-version). 

It might be necessary to update a specific Pimcore module to a version that is not included in the Platform Version.
In that case, you need to remove the `platform-version` dependency from your `composer.json` and update the module to
the desired version.
Be aware that this might lead to a theoretically compatible but untested combination of Pimcore modules.

## Other demo/skeleton packages
- [Pimcore Basic Demo](https://github.com/pimcore/demo)
