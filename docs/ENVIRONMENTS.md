# Environments

Dieses Dokument beschreibt die Env-Architektur mit Pipeline/Phase/Profil.

## Kontext

- `PIPELINE`: Projekt-Pipeline (z. B. `dev`, `smoketest`, `delivery`)
- `PHASE`: Pipeline-Phase (z. B. `setup`, `build`, `runtime`, `deploy`)
- `PROFILE`: optionales Profil (z. B. `dev`, `preview`, `prod`)

## Referenzen

- Beispielwerte: `.env.template`
- Struktur/Regeln: `config/env.manifest.yaml` (variables + pipelines)

## Dotenv-Ladereihenfolge

1) System-Env
2) `.env`
3) `.env.local`
4) `.env.<PIPELINE>`
5) `.env.<PIPELINE>.local`
6) `.env.<PIPELINE>.<PROFILE>` (optional)
7) `.env.<PIPELINE>.<PROFILE>.local` (optional)

## Regeln

- `config/env.manifest.yaml` definiert `variables` (Bereiche + Quellen) und `pipelines`.
- `allowed` kann Gruppen aus `variables` oder einzelne Keys enthalten.
- `sources` im Manifest erzwingt, aus welchen Quellen Variablen kommen duerfen (z. B. nur `system` oder `local`).
- Build erzeugt `var/config/env.php` als aufgeloeste Runtime-Konfiguration.
- Runtime liest nur `var/config/env.php` (kein `getenv()/putenv()`).
- Kompilieren via `php bin/cli env compile --phase runtime --pipeline <name> --profile <name>`.
- Inhaltliche Defaults gehoeren in `.local/content.ini` (keine Env-Variable).
- Labels sind Teil des UI und liegen unter `src/resources/labels.json`.

## Hinweise

- Fuer lokale Entwicklung erzeugt `setup` eine `.env.local` (Demo aus `tests/fixtures/env.local`).
- CI/CD setzt Variablen ueber Workflow-Umgebungen.
