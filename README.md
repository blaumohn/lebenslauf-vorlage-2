# Lebenslauf Vorlage (PHP)

Shared-hosting-taugliches PHP-MVP mit Twig, dateibasierter Persistenz und ohne Cookies.

## Lokal starten

```bash
composer install
composer run setup
composer run dev
```

Aufruf: http://127.0.0.1:8080

Voraussetzungen: PHP >= 8.1, Node.js, Python 3.
Wenn keine `.local/env-*.ini` vorhanden ist, fragt `composer run setup`, ob `tests/fixtures/env-gueltig.ini` als Demo verwendet werden soll.

## Build + Dev (YAML -> JSON -> HTML)

Wenn die Daten als YAML vorliegen, kannst du den kompletten Build-Ablauf nutzen:

```bash
composer run cv:build
composer run cv:dev
```

`cv:build` wandelt YAML zu JSON und rendert die statischen HTML-Dateien via `cv:upload`.
`cv:dev` fuehrt erst den Build aus und startet danach den Dev-Server.
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

## Admin-Workflows (CLI)

### CV hochladen

```bash
composer run cv:upload -- <PROFILE> <JSON_PATH>
```

Erzeugt `var/cache/html/cv-private-<profile>.<lang>.html` pro Sprache. Wenn `<PROFILE>` dem `DEFAULT_CV_PROFILE` entspricht, wird zusaetzlich `cv-public.<lang>.html` erzeugt.
Die Default-Sprache (erstes Element aus `APP_LANGS`, sonst `APP_LANG`) schreibt zusaetzlich die Legacy-Dateien `cv-private-<profile>.html` und `cv-public.html`.
Beim Upload wird gegen `schemas/lebenslauf.schema.json` validiert.

### Tokens rotieren

```bash
composer run token:rotate -- <PROFILE> [COUNT]
```

Gibt neue Tokens einmalig im Terminal aus und schreibt nur Hashes nach `var/state/tokens/<profile>.txt`.

### CAPTCHA Cleanup

```bash
composer run captcha:cleanup
```

Loescht abgelaufene CAPTCHA-Dateien.

## Tests

```bash
composer run test
```

## Isolierte QA-Aufgaben

```bash
composer run qa:setup
composer run qa:dev
```

`qa:setup` klont das Repo in einen temporaeren Ordner, installiert Abhaengigkeiten und nutzt lokale Cache-Verzeichnisse.
`qa:dev` fuehrt zusaetzlich `cv:build`, PHPUnit-Tests und einen `curl`-Check gegen den Dev-Server aus.
Mock-Daten kommen aus `tests/fixtures/daten-default.yaml`.

Optionale Umgebungsvariablen:
- `EXPECTED_GITHUB_USER` prueft den GitHub-Owner der Quelle (via `origin`).
- `CLONE_SOURCE` setzt eine lokale Quelle oder Git-URL (Default: lokales Repo).
- `KEEP_QA_CLONE=1` behaelt die temporaere Clone-Umgebung.

## Templates

- Templates verwenden Twig-Makros statt Includes.
- Basis-UI-Bausteine: `templates/components/site/lib.html.twig`
- Layout/Navigation: `templates/components/site/layout.html.twig`
- Form-Elemente: `templates/components/site/form.html.twig`
- CV: `templates/components/cv/lib.html.twig`, `templates/components/cv/sections.html.twig`, `templates/components/cv/entry.html.twig`
- CV-Layout-Makros: `templates/components/cv/view.html.twig`, `templates/components/cv/page.html.twig`

## Staging/Pre-Release

- Dummy-Daten verwenden.
- Dateirechte fuer `var/` pruefen.
- PHP-GD und Mail testen.
