# Lebenslauf Vorlage (PHP)

Shared-hosting-taugliches PHP-MVP mit Twig, dateibasierter Persistenz und ohne Cookies.

## Lokal starten

```bash
composer install
composer run dev
```

Aufruf: http://127.0.0.1:8080

Optional kannst du beim Dev-Server E-Mail-Ausgabe nach STDOUT aktivieren:

```bash
composer run dev -- --mail-stdout
```

## Konfiguration

- Datei `.env` anlegen (siehe `.env.example`).
- Wichtige Ordner:
  - `var/tmp/` kurzlebig (CAPTCHA + Rate-Limits)
  - `var/cache/` ableitbar (gerendertes HTML)
  - `var/state/` wichtig (Token-Whitelist)
- Labels fuer Abschnittstitel: `labels/etiketten.json` (Sprache via `APP_LANG`).

## Admin-Workflows (CLI)

### CV hochladen

```bash
composer run cv:upload -- <PROFILE> <JSON_PATH>
```

Erzeugt `var/cache/html/cv-private-<profile>.html`. Wenn `<PROFILE>` dem `DEFAULT_CV_PROFILE` entspricht, wird zusaetzlich `cv-public.html` erzeugt.
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

## Staging/Pre-Release

- Dummy-Daten verwenden.
- Dateirechte fuer `var/` pruefen.
- PHP-GD und Mail testen.
