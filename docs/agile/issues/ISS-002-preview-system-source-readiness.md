# ISSUE: Preview Readiness durch konsistente System-Source-Verarbeitung

## Typ
- Issue (Delivery)

## Status-Update (2026-02-03)
- Kernziel in der Config-Lib ist weitgehend erreicht.
- Preview-Workflow wurde bewusst vorerst entfernt (keine halbfertige CI-Stufe).
- Restarbeit wird in Folge-Issues aufgeteilt.

## Erreicht
- `system`-Werte werden im zentralen Resolve-Pfad geladen und gemerged.
- `values()`, `validate()` und `compile()` laufen ueber denselben Resolve-Kern.
- Source-Policy (`allowed`/`required`/`sources`) greift zentral.

## Noch offen
- Repo-Hygiene: lokale Composer-`path`-Abhaengigkeit darf nicht in Delivery-Flow bleiben.
- Architektur-Lesbarkeit: `resolvePhaseConfig(): ?array` ist semantisch/typisch zu schwach.
- Preview-Flow wird erst nach Stabilisierung aus `dev` heraus wieder aufgebaut.

## Scope
- Config-Lib:
  - Loader liest Rohdaten je Quelle (Datei, `system`, CLI-Overrides).
  - Resolve-Pfad merged Quellen in definierter Reihenfolge.
  - Policy validiert `allowed`/`required`/`sources` fuer alle API-Wege.
- App-Repo:
  - Basis fuer spaeteren Preview-Flow ist gelegt.
  - Direkte Preview-Workflow-Details sind in Folge-Issues verschoben.

## Nicht im Scope
- Backlog-Items `BLC-001` und `BLC-002` (Typensystem, zentrales Fehlerkonzept).
- Generisches Composer-Tooling (`ISS-001`).

## Abgrenzung fuer Abschluss dieses Issues
- [x] `system` in zentralem Resolve-Pfad integriert.
- [x] Einheitliches Verhalten fuer `values()`, `validate()` und `compile()`.
- [x] Source-Policy validiert systematisch fuer den zentralen Pfad.
- [ ] Preview-Deploy inkl. CI/Branch-Flow (verschoben nach [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)).
- [ ] Repo-Hygiene bzgl. Composer-Quelle (verschoben nach [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)).

## Abhaengigkeiten
- Config-Lib-Branch: `refactor/no-dotenv-config`
- App-Branch: `refactor/no-dotenv-config-app`
- Story:
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Folge-Issues:
  - [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)
  - [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)
  - [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)

## Workflow-Phase
- Aktuell: In Progress (mit Split in Folge-Issues)
- Naechster Gate: Ready for Close (nach Ticket-Split bestaetigt)
