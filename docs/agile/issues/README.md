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

- `refactor/no-dotenv-config-app` ist inhaltlich fertig.
- Naechster Schritt: PR `refactor/no-dotenv-config-app` -> `dev`.
- Optional vor dem PR: Rebase auf `main` nur zur technischen Bereinigung (nicht fuer `docs/agile`).
- Refactors auf `dev` (zuerst Grundlagen/Lesbarkeit/Stabilitaet).
- Danach `feature/preview` -> PR nach `dev`
- Danach PR `dev` -> `preview`

## Repo-Betrieb (Erledigt)

- Entscheidung am 2026-02-04: Kein Rebase nur fuer die Umstellung von `docs/agile`.
- `docs/agile` wird ab jetzt in `dev` gepflegt.
- Die bestehende Historie im Branch `refactor/no-dotenv-config-app` bleibt unveraendert.
