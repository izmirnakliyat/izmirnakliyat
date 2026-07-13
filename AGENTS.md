# AGENTS.md

## Cursor Cloud specific instructions

This repository is a single product: **"MY Nakliyat"**, a custom framework-less **PHP CMS** (public website + `admin/` panel + CLI/cron scripts) for a Turkish moving company. Stack: PHP 8 + `mysqli` + MySQL/MariaDB, server-rendered PHP, Apache in production (`.htaccess`), Composer for dev tooling only.

### Services / how to run
- **Web app (dev):** from the repo root run `php -S 0.0.0.0:8000`. `index.php` is the front controller (`includes/front_controller.php`) and handles clean-slug routing (e.g. `/teklif-alin`, `/blog`), so no router script argument is needed. Unknown slugs intentionally return HTTP 200 with a soft "noindex" page (SEO soft-404 behavior), not a hard 404.
  - The site auto-detects "local" when the host is `localhost`/`*.local`/`*.test`/private IP (see `config/environment.php`); on localhost it uses the local DB credentials below and no `.env` is required.
- **Database:** MySQL/MariaDB is required. The app (via `config/db.php`) connects on localhost as user `root`, empty password, database `mynakliyat`. MariaDB is installed but is **not auto-started** — start it each session with `sudo service mariadb start`. The `mynakliyat` database persists in the VM snapshot.
- **Lint (static analysis):** `composer lint` (alias for `composer phpstan`). PHPStan runs at level 1 with `phpstan-baseline.neon` active. NOTE: `tools/phpstan.phar` is git-ignored; it is restored from the Composer install (see update script). The committed baseline is **stale relative to the current code** (many files such as `admin/authors.php` are not in it), so a clean run currently reports pre-existing errors — this is a repo condition, not an environment problem.
- **Tests:** `composer test` (PHPUnit) or `composer test:verbose`. The suite in `tests/Unit/` is pure unit tests and does **not** require a database.
- **Build/deploy:** produces a cPanel upload package (`php scripts/build_canli_deploy.php`, `scripts/package-cpanel-upload.ps1`); not needed for local development.

### Non-obvious gotchas
- **PHP mysqli socket:** PHP's default mysqli socket (`/var/run/mysqld/mysqld.sock`) does not resolve here because `/var/run` is a real directory (not a symlink to `/run`). A one-off ini at `/etc/php/8.3/cli/conf.d/99-mynak-mysql-socket.ini` points `mysqli.default_socket`/`pdo_mysql.default_socket` to `/run/mysqld/mysqld.sock`. This is captured in the snapshot; if `localhost` DB connections fail with "No such file or directory", recreate that ini.
- **root auth:** MariaDB `root@localhost` is configured for `mysql_native_password` with an empty password so PHP (`new mysqli('localhost','root','',...)`) can connect via the socket; the default socket/`unix_socket` auth would otherwise reject non-root OS users.
- **No full schema dump:** there is no single install script. Most `admin/` pages create their own tables on first load (`CREATE TABLE IF NOT EXISTS ...`). The public quote/contact forms need a `form_submissions` table (columns per `ajax/process_form.php.backup...`); it was created during setup and persists in the snapshot. Missing tables generally degrade gracefully (pages still render with hardcoded defaults).
- **Many `*~<timestamp>~` files** in the tree are editor backups (also `*.php.backup.*`), not runtime code — ignore them.

### Repo files that are backups/noise
`SUNUCU-YUKLEME-YOLLARI.txt`, `CPANEL-*.txt`, `_dev_backup/`, `_static_backup/`, and `~`-suffixed duplicates are not part of the runtime.
