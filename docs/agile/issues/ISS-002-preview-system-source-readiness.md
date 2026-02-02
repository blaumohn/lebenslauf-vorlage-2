# ISSUE: Preview Readiness durch konsistente System-Source-Verarbeitung

## Typ
- Issue (Delivery)

## Problem
- Preview-Deployment soll CI-ausloesen und stabil pruefbar sein.
- Die Config-Lib verarbeitet `system`-Sources derzeit nicht konsistent mit dem Manifest-Modell.
- Dadurch ist die Preview-Pipeline fehleranfaellig bei Secrets/Systemwerten.

## Zusammenhang
- Ziel ist eine verlaessliche Preview-Leistung vor spaeterem Merge/Promotion.
- Wenn `system`-Werte nicht korrekt verarbeitet werden, ist Preview keine belastbare Integrationsstufe.

## Ziel
- Systemwerte aus Prozessumgebung in den zentralen Resolve-Pfad integrieren.
- Einheitliches Verhalten fuer `values()`, `validate()` und `compile()`.
- Preview-Workflow auf CLI-Config-Pfad ausrichten (kein veralteter Mischbetrieb).

## Scope
- Config-Lib:
  - Loader liest Rohdaten je Quelle (Datei, `system`, CLI-Overrides).
  - Resolve-Pfad merged Quellen in definierter Reihenfolge.
  - Policy validiert `allowed`/`required`/`sources` fuer alle API-Wege.
- App-Repo:
  - Preview-Workflow nutzt `config lint` und CLI-Befehle konsistent.
  - Trigger bleibt auf Branch `preview`, bis Abschluss der Umstellung.

## Nicht im Scope
- Backlog-Items `BLC-001` und `BLC-002` (Typensystem, zentrales Fehlerkonzept).
- Generisches Composer-Tooling (`ISS-001`).

## Akzeptanzkriterien
- `values()`, `validate()` und `compile()` liefern konsistente Ergebnisse fuer gleiche Inputs.
- Ein Wert aus `system` ist nur gueltig, wenn `sources` dies fuer den Key erlaubt.
- Preview-Deploy auf `preview` funktioniert ohne lokale Composer-Path-Abhaengigkeit.
- CI-Fehler sind eindeutig (fehlend, unerlaubte Quelle, unerwarteter Key).

## Abhaengigkeiten
- Config-Lib-Branch: `refactor/no-dotenv-config`
- App-Branch: `refactor/no-dotenv-config-app`

## Workflow-Phase
- Aktuell: In Progress
- Naechster Gate: Ready for Preview Merge
