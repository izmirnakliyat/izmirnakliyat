# AGENTS.md

## Cursor Cloud specific instructions

### What this repo is
A single PHP monolith: the **MY Nakliyat** (İzmir moving-company) public website plus an
`/admin` CMS panel, backed by **MySQL/MariaDB**. There is no build step for the app itself;
it is plain PHP served by a web server. Dev tooling is **PHPUnit 9** (unit tests) and
**PHPStan** (static analysis). Standard command definitions live in `composer.json`
(`scripts` section) — prefer those over reinventing commands.

### Toolchain already provisioned in the VM
- PHP 8.3 CLI with `mysqli`, `mbstring`, `json`, `curl`, `gd` (the app requires >= 8.0).
- MariaDB 10.11 server + client.
- Composer is the bundled `composer.phar` at the repo root (invoke as `php composer.phar ...`).
- The startup update script runs `php composer.phar install` and provisions `tools/phpstan.phar`
  (a gitignored copy of the vendored PHPStan that `composer lint` expects).

### Database (local dev)
- On `localhost`/CLI the app hardcodes credentials in `config/db.php`: user `root`, **empty
  password**, database **`mynakliyat`**, port 3306. `root@localhost` must allow empty-password
  login via `mysql_native_password` (the PHP process runs as a non-root OS user, so socket
  `unix_socket` auth would fail).
- **The database starts empty — there is no schema dump in the repo.** Most tables are created
  lazily the first time the relevant `/admin` page is opened. For the public lead-capture flow
  you only need the `forms` + `form_submissions` tables (see seed below).
- **Socket gotcha:** MariaDB's socket is `/run/mysqld/mysqld.sock` but PHP's
  `mysqli.default_socket` is `/var/run/mysqld/mysqld.sock`, and here `/var/run` is NOT symlinked
  to `/run`. If PHP reports `mysqli::__construct(): (HY000/2002): No such file or directory`,
  (re)create the symlink: `sudo ln -sfn /run/mysqld /var/run/mysqld`. `/run` is a tmpfs, so this
  may need re-doing after a fresh boot.

### Starting services (do NOT put these in the update script)
Start MariaDB (no systemd in the VM):
```bash
sudo mkdir -p /run/mysqld && sudo chown mysql:mysql /run/mysqld
sudo ln -sfn /run/mysqld /var/run/mysqld          # so PHP's mysqli finds the socket
sudo mariadbd --user=mysql >/tmp/mariadb.log 2>&1 &
```
First-time only (data dir already initialized in the snapshot): `sudo mariadb-install-db --user=mysql --datadir=/var/lib/mysql`.
Ensure empty-password root + database exist (idempotent):
```bash
sudo mariadb -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); CREATE DATABASE IF NOT EXISTS mynakliyat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; FLUSH PRIVILEGES;"
```

Run the app with PHP's built-in server. Apache `.htaccess` rewrites pretty (extension-less)
URLs to the front controller (`index.php`); the built-in server needs a small router to
emulate that. Create `/tmp/mynak_router.php`:
```php
<?php
$root = '/workspace';
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$path = realpath($root . $uri);
if ($path && is_file($path) && strpos($path, $root) === 0) { return false; } // serve real files
$_GET['route'] = ltrim($uri, '/');
require $root . '/index.php';
```
Then: `php -S 0.0.0.0:8000 -t /workspace /tmp/mynak_router.php`
Open `http://localhost:8000/`. `mynak_http_host_is_local()` treats `localhost` as local, so the
local DB branch and non-canonical (http) behavior are used automatically.
- Direct `.php` URLs and any `?id=`/`?key=` query params get 301-canonicalized away by the SEO
  pipeline (legacy WordPress cleanup) — expected. Reach pages via their pretty slug instead
  (e.g. `/iletisim`, `/blog`, `/galeri`).

### Seed for the public lead-capture demo
`ajax/process_form.php` and the `[form id=N]` shortcode need a `forms` row; `/iletisim`
(contact) writes to `form_submissions` directly. Minimal seed (schemas copied verbatim from
`admin/form_builder.php` and `ajax/process_form.php`):
```sql
-- forms + form_submissions tables, plus one active form id=1.
-- (form_submissions here is a superset covering both the contact page and the AJAX endpoint.)
```
The contact form at `/iletisim` posts to itself (`action=contact_form`, no CSRF token — uses a
honeypot `fax_number` + rate limiting) and inserts into `form_submissions`. The homepage exposes
the CSRF token as `<meta name="csrf-token">` for the AJAX endpoint `ajax/process_form.php`.

### Lint / test / run
- Tests: `php composer.phar test` (PHPUnit; 168 tests, a few intentionally skipped). Unit tests
  do not need the database.
- Lint: `php composer.phar lint` (PHPStan via `tools/phpstan.phar`, baseline in
  `phpstan-baseline.neon`). **Known pre-existing issue:** the committed baseline does not cover
  ~39 files added in the same initial commit, so this currently reports ~212 pre-existing
  `Variable $conn might not be defined.` errors. This is a repo state issue (stale baseline), not
  an environment problem — do not regenerate the baseline unless asked.
- Quick DB/PHP health check: `php scripts/smoke_check.php` (requires MariaDB running).
