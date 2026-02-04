# Issue-Plan (Stand 2026-02-03)

## Ueberblick

- [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md): Qualitaetsrahmen fuer App und Config-Lib
- [ISS-002](ISS-002-preview-system-source-readiness.md): System-Source-Readiness (Basis gelegt, Split aktiv)
- [ISS-003](ISS-003-phase-rules-typing-and-clarity.md): Risikomuster repo-weit feststellen und Befundliste erstellen
- [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md): `dev`-Baseline und Repo-Hygiene
- [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md): Preview-Workflow aus `dev` wieder aktivieren

## Abhaengigkeiten

- [ISS-002](ISS-002-preview-system-source-readiness.md) -> [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md) -> [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)
- [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md) -> [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)
- [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md) -> [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)
- [ISS-002](ISS-002-preview-system-source-readiness.md) -> [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)
- [ISS-002](ISS-002-preview-system-source-readiness.md) -> [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)
- [ISS-003](ISS-003-phase-rules-typing-and-clarity.md) -> [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)
- [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md) -> [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)

## Geplanter Branch-Flow

- Naechster Schritt: `refactor/no-dotenv-config` zuerst auf den neuen `dev`-Branch ueberfuehren (bei Bedarf per Rebase auf `main` vorbereiten)
- `main` -> PR `refactor/no-dotenv-config` -> `dev`
- Refactors auf `dev` (zuerst Grundlagen/Lesbarkeit/Stabilitaet)
- Danach `feature/preview` -> PR nach `dev`
- Danach PR `dev` -> `preview`

## Naechster Repo-Betrieb (Vorschlag)

- Ziel: `docs/agile` soll in `dev` landen und nicht im `refactor/no-dotenv-config-app`-Branch verbleiben.
- Vorgehen:
  - Agile-Doku als eigener Commit abtrennen.
  - `dev` von `main` anlegen und den Doku-Commit uebernehmen (z. B. per Cherry-pick).
  - `refactor/no-dotenv-config-app` per Rebase bereinigen, sodass `docs/agile` dort nicht mehr enthalten ist.
