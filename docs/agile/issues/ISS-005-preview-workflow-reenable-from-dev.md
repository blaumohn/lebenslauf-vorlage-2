# ISSUE: Preview-Workflow aus dev wieder aktivieren

## Typ
- Issue (Delivery)

## Problem
- Preview-Workflow wurde bewusst entfernt, um keinen instabilen Zwischenstand zu betreiben.
- Ohne geregelten Wiedereinstieg fehlen Trigger, Checks und reproduzierbarer Deploy-Pfad.

## Ziel
- Preview-Workflow nach `dev`-Stabilisierung gezielt wieder aufbauen.
- Eindeutige CI-Fehler fuer Config-Probleme liefern.
- Branch-Trigger und Promotion-Fluss klar dokumentieren.

## Entscheidungen (2026-02-05)
- Preview-Daten sollen direkt aus versionierten Fixtures kommen.
- `LEBENSLAUF_DATEN_PFAD` zeigt fuer Preview auf `tests/fixtures/lebenslauf`.
- Standardprofil fuer Preview soll `gueltig` sein (statt `default`).
- `--create-demo-content` ist kein Pflichtschritt fuer Preview-Deploy.
- `act` wird wegen Komplexitaet vorerst nicht genutzt (spaeter optional).
- `dev` nutzt keinen operativen Deploy-Pfad; ungenutzte `dev`-Deploy-Phase soll entfernt oder klar deaktiviert werden.

## Scope
- CI/Workflow:
  - Trigger auf `preview` (oder explizit dokumentierte Alternative)
  - `config lint` als Pflichtschritt
  - Setup/Build im CLI-Flow (`php bin/cli ...`)
  - zusaetzliche Deploy-Checks neben Linting (Artefakt-Check + Smoke-Checks)
- Deploy:
  - Preview-Deploy ohne lokale Sonderpfade
  - erwartete Env-Secrets und Fehlerfaelle dokumentieren
  - vor Promotion `dev` -> `preview`: Testluecken pruefen und Ablaufpfad einmal komplett gegenlesen
  - kein echter Mailversand in Preview (nur nicht-produktiver Versandpfad)
- Konfiguration:
  - Preview-Phasenwerte explizit pflegen (`setup`, `build`, `runtime`, `deploy`)
  - Runtime-Pflichtwerte fuer Preview explizit erzwingen
  - `build`-Allowed-Liste auf tatsaechlich benoetigte Gruppen/Keys reduzieren

## Nicht im Scope
- Grundlegende Refactor-Arbeit an Config-Modellen (siehe [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)).
- Branch-Baseline und Repo-Hygiene (siehe [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)).

## Akzeptanzkriterien
- Preview-Workflow laeuft reproduzierbar aus einem stabilen `dev`-Stand.
- `config lint` ist vor Build/Deploy verbindlich.
- Fehler sind klar unterscheidbar (fehlend, unerlaubte Quelle, unerwarteter Key).
- Flow ist dokumentiert: `feature/*` -> `dev` -> `preview`.
- Preview-Build laeuft mit Fixtures (`tests/fixtures/lebenslauf`) ohne manuelles Demo-Kopieren.
- Preview-Runtime nutzt keinen echten SMTP-Versand.
- Deploy-Qualitaet ist zusaetzlich zu Linting abgesichert (Artefakt + Smoke).

## Abhaengigkeiten
- Story-Kontext (parallel/nachgelagert):
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Voraussetzungen:
  - [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md) (Done: 2026-02-04)

## Workflow-Phase
- Aktuell: In Progress (Config-Matrix + Workflow-Checks umgesetzt)
- Naechster Gate: Ready for Preview Trial (nach erstem Deployment-Durchlauf)

## Umsetzungsstand (2026-02-05)
- Preview-Configdateien sind angelegt (`preview-setup`, `preview-build`, `preview-runtime`).
- Preview-Build nutzt Fixtures aus `tests/fixtures/lebenslauf` mit Profil `gueltig`.
- Manifest-Regeln fuer `preview` sind geschaerft (Pflichtwerte fuer `build`/`runtime`/`deploy`).
- Workflow prueft zusaetzlich Artefakt + Smoke-HTTP-Checks vor FTP-Deploy.
- `dev-deploy`-Datei wurde entfernt (kein operativer Deploy-Pfad in `dev`).

## Naechste Schritte (P0/P1)
- P0: `cli config lint <pipeline>` fuer alle Phasen bereitstellen und im Workflow nutzen.
- P0: Begriff `Pipeline-Kontext` fuer `pipeline + phase` in CLI-Hilfe und Doku vereinheitlichen.
- P1: CI-Schritte aus Workflow nach `bin/ci/*` auslagern, damit lokal und in GitHub gleich getestet wird.
- P1: Config-Werte fuer Preview auf produktionsnahe Defaults pruefen; reine Beispielwerte in Doku/Schema auslagern.
- P1: Feature-bezogene Tests explizit nachziehen (Build/Runtime/Deploy-Smoke), fehlende Tests als offene Punkte dokumentieren.
