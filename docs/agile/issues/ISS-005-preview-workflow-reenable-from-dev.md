# ISSUE: Preview-Workflow aus dev wieder aktivieren

## Typ
- Issue (Delivery)

## Status
- Aktiv

## Problem
- Preview-Workflow wurde bewusst entfernt, um keinen instabilen Zwischenstand zu betreiben.
- Ohne geregelten Wiedereinstieg fehlen Trigger, Checks und reproduzierbarer Deploy-Pfad.

## Ziel
- Preview-Workflow nach `dev`-Stabilisierung gezielt wieder aufbauen.
- Eindeutige CI-Fehler fuer Config-Probleme liefern.
- Branch-Trigger und Promotion-Fluss klar dokumentieren.

## Entscheidungen (2026-02-05)
- Preview-Daten kommen direkt aus versionierten Fixtures unter `src/resources/fixtures/lebenslauf`.
- `LEBENSLAUF_DATEN_PFAD` zeigt fuer Preview auf `src/resources/fixtures/lebenslauf`.
- Standardprofil fuer Preview bleibt `gueltig` (statt `default`).
- `cli setup <pipeline> --reset-sample-content --rotate-ip-salt` ersetzt `--create-demo-content` (bewusstes Ueberschreiben/Rotieren fuer lokale Daten).
- `act` (lokaler GitHub-Actions-Runner) wird wegen Komplexitaet vorerst nicht genutzt; spaeter optional pruefen.
- `dev` nutzt keinen operativen Deploy-Pfad; ungenutzte `dev`-Deploy-Phase wird entfernt oder klar deaktiviert.
- Beispielwerte gehoeren nicht in Preview-Configs; sie stehen als Metadaten je Variable im Manifest (`meta.desc`, `meta.example`, `meta.notes`).
- Aktive Config-Dateien enthalten nur betriebliche Werte; reine Beispielwerte sind entfernt (fehlende echte Werte bleiben bewusst leer).

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
  - Metadaten je Variable im Manifest ergaenzen (`meta.desc`, `meta.example`)
- Dokumentation:
  - Doku zu Preview-Defaults aktualisieren (z. B. `docs/ENVIRONMENTS.md`)
  - Beispiel: `docs/ENVIRONMENTS.md` darf keine aktive Config als Quelle fuer Beispielwerte referenzieren (z. B. `dev-build.yaml`); Beispielwerte gehoeren in Manifest-Metadaten.

## Nicht im Scope
- Grundlegende Refactor-Arbeit an Config-Modellen (siehe [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)).
- Branch-Baseline und Repo-Hygiene (siehe [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md)).

## Akzeptanzkriterien
- Preview-Workflow laeuft reproduzierbar aus einem stabilen `dev`-Stand.
- `config lint` ist vor Build/Deploy verbindlich.
- Fehler sind klar unterscheidbar (fehlend, unerlaubte Quelle, unerwarteter Key).
- Flow ist dokumentiert: `feature/*` -> `dev` -> `preview`.
- Preview-Build laeuft mit Fixtures (`src/resources/fixtures/lebenslauf`) ohne manuelles Demo-Kopieren; lokale Daten via `--reset-sample-content`.
- Preview-Runtime nutzt keinen echten SMTP-Versand.
- Deploy-Qualitaet ist zusaetzlich zu Linting abgesichert (Artefakt + Smoke).

## Notizen (Repo)
- Mocks/Fixtures liegen ausserhalb von `tests/` unter `src/resources/fixtures/lebenslauf`.
- Manifest: `APP_BASE_PATH`-Meta um `notes` ergaenzt und Beschreibung praezisiert (URL-Pfad vs Dateisystem).
- Twig: Global `base_path` entfernt, nur `path()` bleibt.
- Config: Beispielwerte aus `dev`/`preview` entfernt; Metadaten ins Manifest verschoben.
- Doku: `ENVIRONMENTS.md` verweist auf `meta.example`/`meta.notes`.

## Abhaengigkeiten
- Story-Kontext (parallel/nachgelagert):
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Voraussetzungen:
  - [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md) (Done: 2026-02-04)
  - [ISS-009](ISS-009-json-local-automation-layer.md) (lokale Automationswerte)

## Workflow-Phase
- Aktuell: In Progress (Config-Matrix + Workflow-Checks umgesetzt)
- Naechster Gate: Ready for Preview Trial (nach erstem Deployment-Durchlauf)

## Umsetzungsstand (2026-02-08)
- P1-A (erledigt): Beispielwerte aus Preview/Dev-Configs entfernen; nur betriebliche Werte behalten.
- P1-B (erledigt): Manifest-Metadaten (`meta.desc`, `meta.example`, `meta.notes`) fuer betroffene Variablen ergaenzen.
- P1-C (erledigt): Doku auf Manifest-Metadaten als Beispielquelle umstellen (keine aktiven Config-Dateien als Referenz).
- Preview-Configdateien sind angelegt (`preview-setup`, `preview-build`, `preview-runtime`).
- Preview-Build nutzt Fixtures aus `src/resources/fixtures/lebenslauf` mit Profil `gueltig`.
- Manifest-Regeln fuer `preview` sind geschaerft (Pflichtwerte fuer `build`/`runtime`/`deploy`).
- Workflow prueft zusaetzlich Artefakt + Smoke-HTTP-Checks vor FTP-Deploy.
- `dev-deploy`-Datei wurde entfernt (kein operativer Deploy-Pfad in `dev`).
- CLI/Doku-Terminologie fuer `pipeline + phase` wurde auf `Pipeline-Phase` umgestellt (separater Commit-Block).
- `cli config lint <pipeline>` prueft standardmaessig alle Phasen und wird im Preview-Workflow genutzt.
- CI-Logik fuer Preview ist in `bin/ci` gebuendelt (`ci config-check preview`, `ci smoke preview`).
- Manifest-Metadaten wurden ergaenzt; `meta.notes` ist eingefuehrt.
- CLI-Tooling: `shared`-Ordner in `util` umbenannt, um den Zweck zu klaeren.
- P0 (erledigt): `MAIL_STDOUT` mit `meta.notes` ergaenzt.
- P0 (erledigt): `IP_SALT` lokal via `cli setup <pipeline> --rotate-ip-salt` (nicht versioniert).
- P0 (erledigt): `SMTP_FROM_NAME` aus `required` fuer `dev`/`preview` entfernt.

## Naechste Schritte (P0/P1)
- P0: Kurzfristige Runtime-Validierung fuer dev/preview glaetten:
- Hinweis: `--rotate-ip-salt` bleibt bewusst simpel (Regex-Update in lokaler YAML). Langfristig: Automation ueber lokale JSON-Schicht in [ISS-009](ISS-009-json-local-automation-layer.md).
- P1-D: Feature-bezogene Tests explizit nachziehen (Build/Runtime/Deploy-Smoke), fehlende Tests als offene Punkte dokumentieren.
