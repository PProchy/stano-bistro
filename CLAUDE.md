# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Website and small admin panel for a restaurant (Stano Bistro), built on Nette 3 + Latte, PHP 8.5, MySQL. The public site shows static info, a gallery and the food/drink menus. Each menu is an uploaded PDF linked from the navigation. The admin manages those menu items and the admin users.

User-facing texts (UI, flash messages, form labels) are in Czech. Code, comments, log messages and commit messages are in English.

## Development

Everything runs in Docker. There is no local PHP toolchain.

```bash
docker-compose up -d --build                     # app :8080, phpMyAdmin :8081, MySQL :3307 (root/root)
docker-compose exec web composer install
docker-compose exec web rm -rf temp/cache/*      # clear Nette/Latte cache after config/DI changes
docker-compose down -v && docker-compose up -d --build   # reset DB from docker/init.sql
```

There are no tests, linters or build steps. `nette/tester` is in require-dev but unused. Frontend assets (Bootstrap 5, AdminLTE 3, Font Awesome) come from CDNs. Datagrid assets are committed in `www/dist/`.

`docker/init.sql` holds the schema and seed data for local development (the admin user is a local-only account) and is only imported when the `db_data` volume is first created. Schema changes must be applied manually, both locally and in production.

## Architecture

- **Bootstrap / config** (`app/Bootstrap.php`): loads `config/common.neon`, then `config/services.neon`, then `config/local.neon` **if it exists**. `local.neon` is per environment and not in git (template: `config/local.neon.example`): it holds the DB credentials everywhere, and locally also `debugMode: true` and a disabled cache. Debug mode is on only when `local.neon` sets `parameters: debugMode: true` (read before the configs are loaded). `common.neon` has no credentials. On the server, `config/local.neon` is uploaded by hand and never touched by the deploy.
- **Modules**: presenters are mapped as `App\Modules\<Module>\<Name>Presenter`. Templates live next to them in `app/Modules/<Module>/templates/`.
  - `Front`: public site. `BasePresenter::beforeRender()` loads visible menu items (`$menus`) for the navigation on every page. `Homepage` has actions `default`, `gallery`, `contact` and `poster`, all template-only.
  - `Admin`: routed under `/administrace/<presenter>/<action>`. `AdminPresenter` is the base class: it redirects to `Login` in `startup()` if the user is not logged in. `LoginPresenter` extends the plain Nette `Presenter`, not `AdminPresenter`. Roles are `admin` and `editor`. Managing users requires `admin` (`UserPresenter::requireAdmin()`). Everyone else can edit only their own account.
- **Admin presenter DI**: subclasses of `AdminPresenter` are registered explicitly in `config/services.neon` with constructor arguments `(logger, userFacade, menuFacade, %uploadDir%)`. A new admin presenter needs a matching entry there.
- **Model** (`app/Model`): facades over Nette Database Explorer. `BaseFacade` gives generic CRUD (`get`, `findBy`, `create`, `edit`, `delete`). Subclasses only implement `getTableName()`. `UserFacade` is also the `Nette\Security\Authenticator`, which logs users in by email and password.
- **Tables**: `menu` (`button_name`, `file_name`, `order`, `visible`; `file_path` is legacy and always empty) and `user` (role enum `admin` / `editor` / `user`).
- **Menu PDFs**: uploaded to `www/uploads/` (`%uploadDir%`), stored by sanitized original filename, and referenced by `menu.file_name`. `MenuPresenter::deleteUnusedFile()` deletes a file only when no other row still references it. Keep that invariant when touching uploads.
- **Admin grids**: use `ublaboo/datagrid` 7, namespaced `Contributte\Datagrid\…`.
- **Components**: reusable controls live in `app/Components/<Name>/`, with their `.latte` file next to the class (e.g. `LoginForm`).

## Deployment

Shared hosting (WEDOS), deployed over FTPS by `.github/workflows/deploy.yml`. A push to `main` deploys to test; production is deployed only by running the workflow manually with `environment: production`.

| Environment | URL | App dir (`APP_DIR`) | Document root (`DOCROOT_DIR`) |
|---|---|---|---|
| production | stano-bistro.cz | `/stano-bistro` | `/www/domains/stano-bistro.cz` |
| test | test.stano-bistro.cz | `/stano-bistro-test` | `/www/domains/test.stano-bistro.cz` |

- The app dir (`app`, `config`, `vendor`, `log`, `temp`) lives outside the document root, which holds the contents of `www/`. The workflow rewrites the `vendor/autoload.php` path in `www/index.php` accordingly; the repo version stays as is for Docker.
- Paths and FTP credentials are GitHub environment variables and secrets, not in the repo.
- The deploy only uploads changed files and deletes only files it uploaded itself (sync state in `.ftp-deploy-sync-state.json` on the server), so `www/uploads/`, `config/local.neon`, `log/` and `temp/` are never touched. It drops `temp/cache` at the end.
- On test, the deploy appends `X-Robots-Tag: noindex, nofollow` to `www/.htaccess`. Do not add `Disallow` to `robots.txt` there: search engines would then never see the header. Test has its own DB (a copy of production) and no debug mode.
- The root `.htaccess` is a copy of the WEDOS router in `/www/.htaccess` and is not deployed. Logs go to `log/` through Tracy.
