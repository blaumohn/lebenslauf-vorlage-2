# Environments

Dieses Dokument beschreibt die Config-Architektur mit Pipeline/Phase.

## Kontext

- `PIPELINE`: Projekt-Pipeline (z. B. `dev`, `smoketest`, `delivery`)
- `PHASE`: Pipeline-Phase (z. B. `setup`, `build`, `runtime`, `deploy`, `python`)

## Referenzen

- Beispielwerte: `config/dev-build.yaml`, `config/dev-runtime.yaml`, `config/dev-python.yaml`
- Struktur/Regeln: `config/config.manifest.yaml` (variables + pipelines)

## Config-Ladereihenfolge

1) `config/common.yaml` (optional)
2) `config/<PIPELINE>.yaml`
3) `.local/<PIPELINE>.yaml`
4) `config/<PIPELINE>-<PHASE>.yaml`
5) `.local/<PIPELINE>-<PHASE>.yaml`

Beispiel: `config/dev-build.yaml`, `.local/dev-runtime.yaml`.

## Regeln

- `config/config.manifest.yaml` definiert `variables` (Bereiche + Quellen) und `pipelines`.
- `allowed` kann Gruppen aus `variables` oder einzelne Keys enthalten.
- `sources` im Manifest erzwingt, aus welchen Quellen Variablen kommen duerfen (z. B. nur `system` oder `local`).
- Build erzeugt `var/config/config.php` als aufgeloeste Runtime-Konfiguration.
- Runtime liest nur `var/config/config.php` (kein `getenv()/putenv()`).
- Kompilieren via `php bin/cli config compile <pipeline> --phase runtime`.
- Inhaltliche Defaults (z. B. Lebenslauf-Sprachen) liegen in Config-Keys.
- Labels sind Teil des UI und liegen unter `src/resources/labels.json`.
- Die Phase `python` ist fuer den Python-Runner und nutzt `PYTHON_CMD`/`PYTHON_PATHS`.

## CLI-Modell

Phasen werden direkt ausgefuehrt:

```
cli <phase> <pipeline> [args]
```

Beispiele:

- `php bin/cli setup dev`
- `php bin/cli build dev cv`
- `php bin/cli run dev`
- `php bin/cli python dev --add-path . tests/py/smoke.py`

## Hinweise

- `.local/` ist nicht versioniert und ueberschreibt jeweils `config/`.
- CI/CD kann Werte per `.local/<PIPELINE>-<PHASE>.yaml` bereitstellen oder ueberschreiben.
- Required-Keys sollten in `config/` liegen; `.local/` ist nur fuer Overrides/Secrets gedacht.

## Smoke-Test-Parameter

- `SMOKE_CACHE_ROOT` setzt optionale Cache-Verzeichnisse für Composer/NPM/PIP.
- `TMPDIR` kann für Testläufe gesetzt werden, falls das System-Temp-Verzeichnis nicht nutzbar ist.
