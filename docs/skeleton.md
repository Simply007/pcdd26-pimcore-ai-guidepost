# Pimcore skeleton notes

This repository started from [`pimcore/skeleton`](https://github.com/pimcore/skeleton), the official starting point for new Pimcore projects, maintained by Pimcore GmbH. This page keeps the useful parts of the skeleton's original README. The project-specific quick start lives in the [main README](../README.md).

## Starting a fresh skeleton (not this repository)

The skeleton is meant for developers who start a new Pimcore project from the ground up:

```bash
COMPOSER_MEMORY_LIMIT=-1 composer create-project pimcore/skeleton --no-scripts my-project
cd ./my-project
./vendor/bin/pimcore-install --install-profile='App\Installer\SkeletonProfile'
```

- Point your virtual host to `my-project/public`.
- Apache only: create `my-project/public/.htaccess` according to the [Apache configuration docs](https://pimcore.com/docs/platform/Pimcore/Installation_and_Upgrade/System_Setup_and_Hosting/Apache_Configuration/).
- Open `https://your-host/pimcore-studio` in your browser.

The same works without a local PHP environment through the `pimcore/pimcore` image:

```bash
docker run -u `id -u`:`id -g` --rm -v `pwd`:/var/www/html pimcore/pimcore:php8.4-latest composer create-project pimcore/skeleton --no-scripts my-project
```

## Docker notes

- Your user must be allowed to run docker commands (directly or via sudo), you need Docker Compose, and your user must be allowed to change file permissions.
- `docker-compose.yaml` already defines every required service: PHP, Nginx, MariaDB, Redis, RabbitMQ, OpenSearch, Mercure, and a Supervisord service that runs the messenger workers. You do not need to add any services yourself.
- On Linux, set the container user to your own ids so files written by the containers stay yours: `sed -i "s|#user: '1000:1000'|user: '$(id -u):$(id -g)'|g" docker-compose.yaml` (the `user` lines ship commented out).
- The committed `.env` provides the database, OpenSearch, RabbitMQ, Mercure, and admin values, so the installer does not re-prompt for them. It only asks for the product key (product registration is mandatory, see <https://license.pimcore.com/register>).
- The default admin login comes from `.env`. Change the password after first login.
- The installer also builds the search index automatically (a GenericDataIndex post-install command). This can take a while.

## Running the Codeception tests

```bash
docker compose run --user=root --rm test-php chown -R $(id -u):$(id -g) var/ public/var/
docker compose run --rm test-php vendor/bin/pimcore-install -n
docker compose run --rm test-php vendor/bin/codecept run -vv
```

## Pimcore Platform Version

By default, Pimcore Platform Version is added as a dependency, which ensures installation of compatible versions of additional Pimcore modules that are tested in combination with each other. More information: [Platform Version docs](https://github.com/pimcore/platform-version).

It might be necessary to update a specific Pimcore module to a version that is not included in the Platform Version. In that case, remove the `platform-version` dependency from `composer.json` and update the module to the desired version. Be aware that this might lead to a theoretically compatible but untested combination of Pimcore modules.

## Other demo and skeleton packages

- [Pimcore Basic Demo](https://github.com/pimcore/demo) (archived, frozen on the 2025.x line)
