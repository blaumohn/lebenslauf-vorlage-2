# ISSUE: Generisches Dev-Tooling fuer lokale Composer-Lib-Verknuepfung

## Kontext
- Fuer parallele Entwicklung von App + Lib wird lokal ein `path`-Repository in `composer.json` verwendet.
- Diese lokale Variante darf nicht in normale Commits/PRs gelangen.
- Aktuell existiert ein projektspezifischer Ansatz; Ziel ist ein generisches Tool fuer mehrere Composer-Projekte.

## Ziel
- Ein wiederverwendbares Tool in separatem Repo (z. B. `composer-tools`) bereitstellen.
- Lokale Dev-Umstellung automatisieren, ohne CI/Deploy-Composer zu gefaehrden.

## Scope (MVP)
- Installer-Skript
  - stellt sicher, dass `.local/` existiert
  - fuegt `.local/` in `.gitignore` ein (falls fehlt)
  - kopiert `set-dev-lib-repo.py` nach `.local/bin/`
  - kopiert Hook nach `.local/githooks/pre-commit`
  - setzt lokal `core.hooksPath=.local/githooks`
  - speichert vorhandenen Hook-Pfad in `hooks.previousPath` und leitet weiter
- Beispiel-Datei: `dev-lib-repos.json`
- Skriptnamen generisch halten:
  - `set-config-spec-repo.py` -> `set-dev-lib-repo.py`
  - `config-spec-repos.json` -> `dev-lib-repos.json`

## Akzeptanzkriterien
- Tool laeuft in einem beliebigen Composer-Projekt ohne projektspezifische Konstanten.
- Pre-commit blockiert lokale `path`-Repo-Commits (mit klarer Fehlermeldung).
- Vorhandene Pre-Commit-Hooks laufen weiterhin (Weiterleitung, keine Ersetzung).
- Bypass ist moeglich via `git commit --no-verify`.
- Lokale Dateien bleiben unversioniert.

## Offene Fragen
- Nur `pre-commit` oder zusaetzlich `pre-push`?
- Soll das Tool auch `composer.lock` validieren?
- Soll es Profile fuer mehrere Libraries in einer JSON-Datei geben?

## Prioritaet
- Mittel, kann nach Preview/Prod-Ablauf umgesetzt werden.
