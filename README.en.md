# Resume Template (PHP)

[Deutsch](README.md) | [English](#resume-template-php)

Shared-hosting-friendly PHP MVP with Twig, file-based persistence, and no cookies.

## Local start

```bash
composer install
php bin/cli setup dev
php bin/cli run dev
```

The same commands are also available as `composer` scripts.

Requirements: PHP >= 8.1, Node.js, Python 3.
Defaults come from `config/env-default.ini`.
If no `.local/env-*.ini` exists, `php bin/cli setup` asks whether to use `tests/fixtures/env-gueltig.ini` as a demo.
`php bin/cli setup` runs `npm install` and `pip install pyyaml`.
`php bin/cli run` starts the Python dev runner (options: `--env`, `--build`, `--mail-stdout`).
Note: `setup` may fall back to system Python, while other commands prefer `.venv`.

## CLI syntax

```bash
# Lifecycle
php bin/cli setup <profile>
php bin/cli build <profile>
php bin/cli run <profile> [--build] [--env <file>] [--mail-stdout]

# Content
php bin/cli cv build <profile>
php bin/cli cv upload <profile> <json>

# Security
php bin/cli token rotate <profile> [count]
php bin/cli captcha cleanup
```

## Build + dev (YAML -> JSON -> HTML)

```bash
php bin/cli cv build dev
php bin/cli build dev
```

`cv build` converts YAML to JSON and renders the static HTML via `cv upload`.
If `LEBENSLAUF_DATEN_PFAD` is a directory, all `daten-<profile>.yaml` files are built.

## Configuration

- Use `.local/env-common.ini` and `.local/env-<profile>.ini` (`APP_ENV` selects the profile).
- Important folders:
  - `var/tmp/` short-lived (CAPTCHA + rate limits)
  - `var/cache/` derived (rendered HTML)
  - `var/state/` important (token whitelist)
- Labels for section titles: `labels/etiketten.json` (language via `APP_LANG` or `APP_LANGS`).
- Multilingual: `APP_LANGS=de,en` writes static HTML per language.
- Optional: single INI file via `APP_ENV_FILE`.

Details on environments and variables: `docs/ENVIRONMENTS.md`.

Preview build in CI: `composer install --no-dev --optimize-autoloader --no-interaction` + `php bin/cli setup preview` + `php bin/cli build preview` (deploy dir via `bin/ci/preview-copy.sh`).
FTP target path for preview: environment variable `FTP_SERVER_DIR`.
Base path for preview without rewrite: `APP_BASE_PATH` (e.g. `/public`).

## Admin workflows (CLI)

### Upload CV

```bash
php bin/cli cv upload <PROFILE> <JSON_PATH>
```

Creates `var/cache/html/cv-private-<profile>.<lang>.html` per language. If `<PROFILE>` equals `DEFAULT_CV_PROFILE`, it also creates `cv-public.<lang>.html`.
The default language (first in `APP_LANGS`, otherwise `APP_LANG`) also writes legacy files `cv-private-<profile>.html` and `cv-public.html`.
JSON is validated against `schemas/lebenslauf.schema.json`.
Note: `APP_ENV` must be set (optional via `--app-env <profile>`).

### Rotate tokens

```bash
php bin/cli token rotate <PROFILE> [COUNT]
```

Outputs new tokens once and stores hashes only in `var/state/tokens/<profile>.txt`.

### CAPTCHA cleanup

```bash
php bin/cli captcha cleanup
```

Deletes expired CAPTCHA files.

## Tests

```bash
composer run test
```

## Smoke tests

```bash
composer run tests:smoke
```

The smoke test clones the repo into a temporary directory, installs dependencies, runs `setup` and `test`, and checks the dev server via `curl`.
Mock data comes from `tests/fixtures/lebenslauf/daten-gueltig.yaml`.

Optional environment variables:
- `EXPECTED_GITHUB_USER` checks the GitHub owner of the source (via `origin`).
- `CLONE_SOURCE` sets a local source or Git URL (default: local repo).
- `KEEP_SMOKE_CLONE=1` keeps the temporary clone.

## Templates

- Templates use Twig macros instead of includes.
- Base UI building blocks: `src/resources/templates/components/site/lib.html.twig`
- Layout/navigation: `src/resources/templates/components/site/layout.html.twig`
- Form elements: `src/resources/templates/components/site/form.html.twig`
- CV: `src/resources/templates/components/cv/lib.html.twig`, `src/resources/templates/components/cv/sections.html.twig`, `src/resources/templates/components/cv/entry.html.twig`
- CV layout macros: `src/resources/templates/components/cv/view.html.twig`, `src/resources/templates/components/cv/page.html.twig`

## Staging/Pre-release

- Use dummy data.
- Check file permissions for `var/`.
- Test PHP-GD and mail.
