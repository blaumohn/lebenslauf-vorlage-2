# STORY: Qualitaetsrahmen fuer App und Config-Lib

## Typ
- Story (uebergeordnet)

## Problem
- Im Repo treten wiederholt Risikomuster und Inkonsistenzen auf.
- `PhaseConfig` ist ein Beispiel, aber nicht das einzige.
- Ohne systematische Sicht entstehen weiter lokale Einzelfixes statt stabilem Standard.

## Ziel
- Qualitaetskriterien als explizite Anforderungen verankern.
- Risikomuster repo-weit in App und Config-Lib erfassen, priorisieren und schrittweise abbauen.

## Teil-Issues
- [ISS-003](ISS-003-phase-rules-typing-and-clarity.md): Befundliste von Risikomustern und Qualitaetsdefiziten fuer App + Config-Lib erstellen.
- [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md): `dev`-Baseline und Repo-Hygiene stabilisieren.
- [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md): Preview-Workflow nach Stabilisierung wieder aktivieren.

## Abhaengigkeiten
- Eingang:
  - [ISS-002](ISS-002-preview-system-source-readiness.md)

## Workflow-Phase
- Aktuell: In Progress
- Naechster Gate: Ready for Story Breakdown
