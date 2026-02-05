# Issue-Plan (Stand 2026-02-05)

## Ueberblick

- [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md): Qualitaetsrahmen fuer App und Config-Lib
- [ISS-002](ISS-002-preview-system-source-readiness.md): System-Source-Readiness (Basis gelegt, Split aktiv)
- [ISS-003](ISS-003-phase-rules-typing-and-clarity.md): Risikomuster repo-weit feststellen und Befundliste erstellen
- [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md): `dev`-Baseline und Repo-Hygiene (erledigt am 2026-02-04)
- [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md): Preview-Workflow aus `dev` wieder aktivieren

## Abhaengigkeiten

- [ISS-002](ISS-002-preview-system-source-readiness.md) -> [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md) -> [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)
- [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md) -> [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)
- [ISS-002](ISS-002-preview-system-source-readiness.md) -> [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)
- [ISS-002](ISS-002-preview-system-source-readiness.md) -> [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)
- [ISS-002](ISS-002-preview-system-source-readiness.md) -> [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)
- [ISS-003](ISS-003-phase-rules-typing-and-clarity.md) -> [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)
- [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md) -> [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)

## Geplanter Branch-Flow

- PR `refactor/no-dotenv-config-app` -> `dev` ist erledigt (2026-02-04).
- Danach `feature/preview` -> PR nach `dev`
- Danach PR `dev` -> `preview`
- Danach Refactors auf `dev` (zuerst Grundlagen/Lesbarkeit/Stabilitaet, inkl. Testluecken-Check und Ablaufpfad-Review).

## Fokus in ISS-005 (2026-02-05)

- Preview nutzt Fixtures direkt ueber Config (`tests/fixtures/lebenslauf`), kein Pflicht-`--create-demo-content`.
- Standardprofil fuer Preview: `gueltig`.
- `dev` hat keinen operativen Deploy-Pfad; `dev`-Deploy wird entfernt oder klar deaktiviert.
- Deployment-Checks werden ueber Linting hinaus ergaenzt (Artefakt-Check + Smoke-Checks).

## Repo-Betrieb (Erledigt)

- Entscheidung am 2026-02-04: Kein Rebase nur fuer die Umstellung von `docs/agile`.
- `docs/agile` wird ab jetzt in `dev` gepflegt.
- Die bestehende Historie im Branch `refactor/no-dotenv-config-app` bleibt unveraendert.

## Pflege-Regel

- Wenn ein Issue auf `Done` wechselt: alle Verlinkungen im Ordner `docs/agile/issues` suchen und Statushinweise aktualisieren.
- Empfohlener Check: `rg -n "ISS-[0-9]{3}" docs/agile/issues`.
