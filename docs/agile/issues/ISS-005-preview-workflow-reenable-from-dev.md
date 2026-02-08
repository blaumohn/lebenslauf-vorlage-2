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
- Preview-Daten sollen direkt aus versionierten Fixtures kommen.
- `LEBENSLAUF_DATEN_PFAD` zeigt fuer Preview auf `tests/fixtures/lebenslauf`.
- Standardprofil fuer Preview soll `gueltig` sein (statt `default`).
- `--create-demo-content` ist kein Pflichtschritt fuer Preview-Deploy.
- `act` wird wegen Komplexitaet vorerst nicht genutzt (spaeter optional).
- `dev` nutzt keinen operativen Deploy-Pfad; ungenutzte `dev`-Deploy-Phase soll entfernt oder klar deaktiviert werden.
- Beispielwerte gehoeren nicht in Preview-Configs; sie werden als Metadaten je Variable im Manifest gepflegt (`meta.desc`, `meta.example`, `meta.notes`).
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
- Preview-Build laeuft mit Fixtures (`tests/fixtures/lebenslauf`) ohne manuelles Demo-Kopieren.
- Preview-Runtime nutzt keinen echten SMTP-Versand.
- Deploy-Qualitaet ist zusaetzlich zu Linting abgesichert (Artefakt + Smoke).

## Notizen (Repo)
- Frage fuer naechste Sitzung: Ist es ueblich, Mocks/Fixtures ausserhalb von `tests/` zu halten, um Duplizierung und Wartung zu reduzieren?
- Manifest: `APP_BASE_PATH`-Meta um `notes` ergaenzt und Beschreibung praezisiert (URL-Pfad vs Dateisystem).
- Twig: Global `base_path` entfernt, nur `path()` bleibt.
- Config: Beispielwerte aus `dev`/`preview` entfernt; Metadaten ins Manifest verschoben.
- Doku: `ENVIRONMENTS.md` verweist auf `meta.example`/`meta.notes`.

## Abhaengigkeiten
- Story-Kontext (parallel/nachgelagert):
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Voraussetzungen:
  - [ISS-004](ISS-004-dev-branch-foundation-and-repo-hygiene.md) (Done: 2026-02-04)

## Workflow-Phase
- Aktuell: In Progress (Config-Matrix + Workflow-Checks umgesetzt)
- Naechster Gate: Ready for Preview Trial (nach erstem Deployment-Durchlauf)

## Umsetzungsstand (2026-02-08)
- P1-A (erledigt): Beispielwerte aus Preview/Dev-Configs entfernen; nur betriebliche Werte behalten.
- P1-B (erledigt): Manifest-Metadaten (`meta.desc`, `meta.example`, `meta.notes`) fuer betroffene Variablen ergaenzen.
- P1-C (erledigt): Doku auf Manifest-Metadaten als Beispielquelle umstellen (keine aktiven Config-Dateien als Referenz).
- Preview-Configdateien sind angelegt (`preview-setup`, `preview-build`, `preview-runtime`).
- Preview-Build nutzt Fixtures aus `tests/fixtures/lebenslauf` mit Profil `gueltig`.
- Manifest-Regeln fuer `preview` sind geschaerft (Pflichtwerte fuer `build`/`runtime`/`deploy`).
- Workflow prueft zusaetzlich Artefakt + Smoke-HTTP-Checks vor FTP-Deploy.
- `dev-deploy`-Datei wurde entfernt (kein operativer Deploy-Pfad in `dev`).
- CLI/Doku-Terminologie fuer `pipeline + phase` wurde auf `Pipeline-Phase` umgestellt (separater Commit-Block).
- `cli config lint <pipeline>` prueft standardmaessig alle Phasen und wird im Preview-Workflow genutzt.
- CI-Logik fuer Preview ist in `bin/ci` gebuendelt (`ci config-check preview`, `ci smoke preview`).
- Manifest-Metadaten wurden ergaenzt; `meta.notes` ist eingefuehrt.

## Naechste Schritte (P0/P1)
- P0: Kurzfristige Runtime-Validierung fuer dev/preview glaetten:
  - `SMTP_FROM_NAME` aus `required` fuer `dev`/`preview` entfernen (bei `MAIL_STDOUT=1` ungenutzt).
  - `MAIL_STDOUT` mit `meta.notes` ergaenzen: SMTP_* nur bei `MAIL_STDOUT=0` notwendig.
  - `IP_SALT` lokal generieren (nicht versionieren), z. B. via Helper in `src/cli/php/shared`.
- P1-D: Feature-bezogene Tests explizit nachziehen (Build/Runtime/Deploy-Smoke), fehlende Tests als offene Punkte dokumentieren.