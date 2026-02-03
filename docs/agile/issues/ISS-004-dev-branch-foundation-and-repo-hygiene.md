# ISSUE: Dev-Basis herstellen und Repo-Hygiene absichern

## Typ
- Issue (Delivery)

## Problem
- Lokale Composer-`path`-Verknuepfung ist im App-Repo sichtbar und gefaehrdet reproduzierbare CI.
- Branch-Zielbild (`dev` als Stabilisierungsebene) ist noch nicht formalisiert.
- Ohne klare Baseline wird der naechste Preview-Anlauf unnoetig instabil.

## Ziel
- `dev` als Integrationsbranch fuer grundlegende Refactors etablieren.
- Repo-Zustand auf CI-faehige, remote reproduzierbare Dependencies bringen.
- Klare Gates definieren, bevor Preview-Workflow wieder aktiviert wird.

## Scope
- Git/Flow:
  - `refactor/no-dotenv-config` zuerst nach `dev` ueberfuehren (bei Bedarf per Rebase auf `main` vorbereiten)
  - PR `refactor/no-dotenv-config` nach `dev`
  - Folge-Refactors zuerst auf `dev`
- Composer-Hygiene:
  - keine fest eingetragene lokale `path`-Quelle fuer `pipeline-config-spec/lib` in Delivery-Stand
  - Lockfile konsistent fuer CI
- Dokumentation:
  - Branch-Regeln und Merge-Gates in Agile-Doku dokumentieren

## Nicht im Scope
- Reaktivierung des Preview-Workflows selbst (siehe [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)).

## Akzeptanzkriterien
- `dev` existiert als offizieller Integrationsbranch fuer Grundlagenarbeit.
- App-Repo ist ohne lokale Path-Abhaengigkeit baubar.
- Merge-Gates fuer `dev` sind dokumentiert (mindestens: Config-Lint, Build, Tests).
- Folge-Preview-Issue kann ohne offene Basisthemen starten.

## Abhaengigkeiten
- Story:
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Eingang:
  - [ISS-002](ISS-002-preview-system-source-readiness.md)
  - [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)
- Wirkt auf:
  - [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)

## Workflow-Phase
- Aktuell: Todo
- Naechster Gate: Ready for Dev Stabilization

## Naechste Aufgabe
- `refactor/no-dotenv-config` aus dem aktuellen Stand zuerst auf den neuen `dev`-Branch ueberfuehren.
- Falls noetig: vorab Rebase auf `main`, damit der PR nach `dev` sauber und fokussiert bleibt.
- Operativer Vorschlag fuer den ersten Repo-Schritt:
  - `docs/agile` als separaten Commit behandeln und nach `dev` ueberfuehren.
  - Danach `refactor/no-dotenv-config-app` per Rebase bereinigen, damit `docs/agile` nicht im Refactor-Branch liegt.
