# Lebenslauf Vorlage (PHP)

[Deutsch](#lebenslauf-vorlage-php) | [English](README.en.md)

Shared-hosting-taugliches PHP-MVP mit Twig, dateibasierter Persistenz und ohne Cookies.

## Lokal starten

```bash
composer install
php bin/cli setup dev
php bin/cli run dev
```

Die gleichen Befehle sind auch als `composer`-Skripte verfuegbar.

Aufruf: http://127.0.0.1:8080

Voraussetzungen: PHP >= 8.1, Node.js, Python 3.
Standardwerte kommen aus `config/env-default.ini`.
Wenn keine `.local/env-*.ini` vorhanden ist, fragt `composer run setup`, ob `tests/fixtures/env-gueltig.ini` als Demo verwendet werden soll.
`php bin/cli setup` fuehrt `npm install` und `pip install pyyaml` aus.
`php bin/cli run` startet den Python-Dev-Runner (Optionen: `--env`, `--build`, `--mail-stdout`).
Hinweis: `setup` darf System-Python als Fallback nutzen, alle anderen Befehle bevorzugen `.venv`.

## CLI Syntax

```bash
# Lebenszyklus
php bin/cli setup <profil>
php bin/cli build <profil>
php bin/cli run <profil> [--build] [--env <file>] [--mail-stdout]

# Inhalte
php bin/cli cv build <profil>
php bin/cli cv upload <profil> <json>

# Sicherheit
php bin/cli token rotate <profil> [count]
php bin/cli captcha cleanup
```

## Build + Dev (YAML -> JSON -> HTML)

Wenn die Daten als YAML vorliegen, kannst du den kompletten Build-Ablauf nutzen:

```bash
php bin/cli cv build dev
php bin/cli build dev
```

`cv build` wandelt YAML zu JSON und rendert die statischen HTML-Dateien via `cv upload`.
Wenn `LEBENSLAUF_DATEN_PFAD` ein Verzeichnis ist, werden alle Dateien `daten-<profil>.yaml` gebaut.

## Konfiguration

- `.local/env-common.ini` und `.local/env-<profil>.ini` verwenden (`APP_ENV` steuert das Profil).
- Wichtige Ordner:
  - `var/tmp/` kurzlebig (CAPTCHA + Rate-Limits)
  - `var/cache/` ableitbar (gerendertes HTML)
  - `var/state/` wichtig (Token-Whitelist)
- Labels fuer Abschnittstitel: `labels/etiketten.json` (Sprache via `APP_LANG` oder `APP_LANGS`).
- Mehrsprachigkeit: `APP_LANGS=de,en` aktiviert pro Sprache statische HTML-Dateien.
- Optional: einzelne INI-Datei ueber `APP_ENV_FILE` setzen.

Details zu Umgebungen und Variablen: `docs/ENVIRONMENTS.md`.

Preview-Build in CI: `composer install --no-dev --optimize-autoloader --no-interaction` + `php bin/cli setup preview` + `php bin/cli build preview` (Deploy-Ordner via `bin/ci/preview-copy.sh`).
FTP-Zielpfad fuer Preview: Environment-Variable `FTP_SERVER_DIR`.
Base-Path fuer Preview ohne Rewrite: `APP_BASE_PATH` (z. B. `/public`).

## Admin-Workflows (CLI)

### CV hochladen

```bash
php bin/cli cv upload <PROFILE> <JSON_PATH>
```

Erzeugt `var/cache/html/cv-private-<profile>.<lang>.html` pro Sprache. Wenn `<PROFILE>` dem `DEFAULT_CV_PROFILE` entspricht, wird zusaetzlich `cv-public.<lang>.html` erzeugt.
Die Default-Sprache (erstes Element aus `APP_LANGS`, sonst `APP_LANG`) schreibt zusaetzlich die Legacy-Dateien `cv-private-<profile>.html` und `cv-public.html`.
Beim Upload wird gegen `schemas/lebenslauf.schema.json` validiert.
Hinweis: `APP_ENV` muss gesetzt sein (optional per `--app-env <profil>`).

### Tokens rotieren

```bash
php bin/cli token rotate <PROFILE> [COUNT]
```

Gibt neue Tokens einmalig im Terminal aus und schreibt nur Hashes nach `var/state/tokens/<profile>.txt`.

### CAPTCHA Cleanup

```bash
php bin/cli captcha cleanup
```

Loescht abgelaufene CAPTCHA-Dateien.

## Tests

```bash
composer run test
```

## Smoke-Tests

```bash
composer run tests:smoke
```

Der Smoke-Test klont das Repo in einen temporaeren Ordner, installiert Abhaengigkeiten, fuehrt `setup` und `test` aus
und prueft den Dev-Server via `curl`. Mock-Daten kommen aus `tests/fixtures/lebenslauf/daten-gueltig.yaml`.

Optionale Umgebungsvariablen:
- `EXPECTED_GITHUB_USER` prueft den GitHub-Owner der Quelle (via `origin`).
- `CLONE_SOURCE` setzt eine lokale Quelle oder Git-URL (Default: lokales Repo).
- `KEEP_SMOKE_CLONE=1` behaelt die temporaere Clone-Umgebung.

## Templates

- Templates verwenden Twig-Makros statt Includes.
- Basis-UI-Bausteine: `src/resources/templates/components/site/lib.html.twig`
- Layout/Navigation: `src/resources/templates/components/site/layout.html.twig`
- Form-Elemente: `src/resources/templates/components/site/form.html.twig`
- CV: `src/resources/templates/components/cv/lib.html.twig`, `src/resources/templates/components/cv/sections.html.twig`, `src/resources/templates/components/cv/entry.html.twig`
- CV-Layout-Makros: `src/resources/templates/components/cv/view.html.twig`, `src/resources/templates/components/cv/page.html.twig`

## Staging/Pre-Release

- Dummy-Daten verwenden.
- Dateirechte fuer `var/` pruefen.
- PHP-GD und Mail testen.
