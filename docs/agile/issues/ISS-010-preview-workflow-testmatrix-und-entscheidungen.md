# ISSUE: Preview-Workflow Testmatrix und offene Entscheidungen

## Typ
- Issue (Delivery/Design)

## Status
- Aktiv

## Abgeleitet aus
- [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md) (P1-D Auslagerung)

## Problem
- In [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md) ist P1-D noch offen.
- Die notwendigen Entscheidungen fuer Testabdeckung, Workflow-Zuschnitt und Vorabpruefungen sind noch nicht als eigenes, abgeschlossenes Arbeitspaket gebuendelt.
- Ohne diese Klaerungen bleibt die Umsetzung von P1-D in ISS-005 unscharf und schwer nachweisbar.

## Ziel
- Vollstaendige Testmatrix fuer Build/Runtime/Deploy-Smoke festlegen.
- Offene Entscheidungen fuer den zukuenftigen Deploy-Workflow explizit treffen.
- Konkrete Restluecken mit Folgepfad dokumentieren.

## Scope
- P1-D-Teststrategie und Nachweisstruktur definieren:
  - Vertrags-Tests (`config lint`) je Pipeline-Phase.
  - Verhaltens-Tests (Build/Runtime/Deploy-Smoke) je relevanter Pipeline.
- Workflow-Entscheidungen fuer Preview und vorbereitend fuer Production ausarbeiten:
  - Parametrisierung, Branch->Environment-Mapping, Preflight-Schritte.
- Offene Punkte in einer zentralen Entscheidungstabelle pflegen.

## Nicht im Scope
- Vollstaendige Implementierung eines Production-Deployments.
- Grundlegende Refactor-Arbeit an Config-Modellen (siehe [ISS-003](ISS-003-phase-rules-typing-and-clarity.md)).

## Entscheidungen und Entwurfsstand (2026-02-10)
- Workflow-Design: Ein gemeinsamer Deploy-Workflow ohne `workflow_call` ist bevorzugt.
- Branch->Environment-Mapping: aktuell nur `preview` aktiv; `main -> prod` bleibt vorbereitet.
- Sicherheitsregel: Deploy-Job darf nur auf freigegebenen Branches laufen (kein generischer Fallback-Deploy auf beliebigen Branch).
- Pipeline-spezifisches Verhalten soll vorrangig in Config abgebildet werden.
- `--rotate-ip-salt` soll im selben Umsetzungsschritt entfernt werden, in dem die Runtime-Guardrails aktiv werden.
- `IP_SALT`-Richtung (Entwurf): Runtime-eigene Verwaltung in `var/state`, inklusive Fingerprint-Pruefung und konsistenter Bereinigung IP-bezogener Tabellen bei Rotation.
- Begriffsabgrenzung: CI/Workflow-Leak und Server-Dateizugriff sind getrennte Bedrohungsmodelle.

## `IP_SALT` Runtime-Verwaltung: Eigenschaften und Gruende
- Zielbild:
  - `IP_SALT` wird zur Laufzeit in `var/state` verwaltet (nicht als regulaerer Config-Wert in versionierten Dateien).
  - Runtime kann fehlenden Salt selbst erzeugen und stabil weiternutzen.
- Gruende:
  - Konsistenz: gleiche Hash-Basis ueber mehrere Requests/Deploys, solange `var/state` persistent bleibt.
  - Betriebsklarheit: Salt-Rotation und IP-Tabellen-Bereinigung sind ein zusammengehoeriger Vorgang.
  - CI-Hygiene: weniger Secret-/Parameter-Fluss nur fuer Salt-Rotation im Deploy-Workflow.
  - Fachbezug: `IP_SALT` ist primär Betriebszustand fuer Hash-Konsistenz, nicht fachlicher Eingabewert.
- Sicherheitsabgrenzung:
  - Runtime-Verwaltung reduziert nicht den Schaden bei kompromittiertem Server-Dateizugriff.
  - Sie reduziert aber Bedienfehler/Inkonsistenzen durch CI-seitige Rotationen ohne State-Bereinigung.
- Technische Leitplanken:
  - atomisches Schreiben (`tmp` + `rename`), Dateilock fuer Parallelzugriffe, restriktive Dateirechte.
  - Fingerprint/Marker zur Erkennung von Salt-Wechseln und automatischer Bereinigung der IP-bezogenen Tabellen.

## Ableitung fuer CLI und Config
- `--rotate-ip-salt`:
  - wird nicht uebergangsweise belassen, sondern mit Einfuehrung der Guardrails direkt entfernt.
  - Rotation erfolgt danach nur noch bewusst ueber einen expliziten Betriebsbefehl (z. B. `cli ip-hash reset`: rotiert Salt + leert IP-Tabellen).
- `IP_SALT` als Config-Key:
  - fuer `dev`/`preview` mittelfristig nicht mehr als Pflicht-Config vorgesehen, wenn Runtime-Verwaltung aktiv ist.
  - fuer `prod` optionaler Override-/Migrationspfad bleibt zunaechst erlaubt, bis Runtime-Verwaltung vollstaendig eingefuehrt ist.
- Workflow-Folge:
  - Salt-Rotation nicht mehr implizit in `setup` jedes Deploys.
  - Rotation nur bewusst ueber Betriebsbefehl bzw. klaren Wartungsschritt.

## Kurzfristige Entfernung von `--rotate-ip-salt` (Guardrails)
- `--rotate-ip-salt` wird kurzfristig entfernt, wenn alle Punkte gleichzeitig umgesetzt sind:
  - Runtime verwaltet `IP_SALT` robust in `var/state` (inkl. Locking + atomischem Schreiben).
  - Bei Salt-Wechsel wird IP-bezogener State automatisch und konsistent bereinigt.
  - Workflow und Skripte verwenden `--rotate-ip-salt` an keiner Stelle mehr.
- Ohne diese Guardrails darf keine Entfernung erfolgen.

## Offene Punkte und Entscheidungen
| Bereich | Entscheidung / Entwurf | Status | Restpunkt |
| --- | --- | --- | --- |
| Workflow-Design | Ein gemeinsamer Deploy-Workflow ohne `workflow_call` ist gesetzt. | done | Job-Guards fuer erlaubte Branches im YAML finalisieren. |
| Branch-Mapping | Aktuell `preview -> preview`; `main -> prod` ist als spaeterer Ausbau vorgesehen. | done | Produktions-Branchregel bei Prod-Einfuehrung verbindlich eintragen. |
| GitHub Environments | `preview` jetzt verbindlich; `production` wird vorbereitet, aber noch nicht aktiv genutzt. | deferred | Aktivierung mit erstem Prod-Issue terminieren. |
| Parameterisierung | Workflow bleibt generisch; pipeline-spezifisches Verhalten vorrangig ueber Config statt Spezialschritte. | done | Bei Runtime-Verwaltung pruefen, welche Setup-Flags entfallen koennen. |
| Rolle von `bin/ci` | Zielbild: direkte Workflow-Schritte; `bin/ci` nur solange behalten, bis Paritaet nachgewiesen ist. | deferred | Abbauplan mit Exit-Kriterien dokumentieren. |
| Testschichten | Pflicht vor Deploy: `config lint`, `composer run test`, Build, Artefakt-Checks. | done | Reihenfolge + Failure-Messages im Workflow vereinheitlichen. |
| Pipeline-Abdeckung | Vertrags-Tests sollen pipelinespezifisch aus dem Manifest abgeleitet werden. | done | Umsetzung im Workflow/Script festziehen. |
| Verhaltens-Tests | Zuschnitt je Pipeline: `dev`-Smoke getrennt, `preview`-Deploy-Smoke verpflichtend. | done | Produktiver Post-Deploy-Smoke fuer `prod` spaeter ergaenzen. |
| Deploy-Artefakt | Runtime-vollstaendige Inhalte muessen explizit geprueft werden. | open | Endgueltige Artefaktliste und Checks festlegen. |
| Smoke-Ort | Pre-Deploy-Smoke bleibt; Post-Deploy-Smoke gegen Ziel-URL ist gewuenscht. | deferred | Stabilen Post-Deploy-Checkpfad definieren. |
| FTP-Preflight | Vor Deploy separater Preflight (inkl. dry-run) ist vorgesehen. | done | Konkrete Action-Parameter final eintragen. |
| SMTP bei `MAIL_STDOUT=0` | Stufenmodell: Verbindungscheck; optional echter Versandtest je Umgebung. | deferred | Entscheidung pro Umgebung in eigener Task fixieren. |
| Kosten/CI-Ressourcen | Service-Container (z. B. Mailpit) sind optional, nicht zwingend fuer P1-D. | deferred | Aufwand/Kosten bei Bedarf gesondert bewerten. |
| `IP_SALT`-Strategie | Entwurf: runtime-nativ in `var/state`; bei Inkonsistenz Rotation + IP-Tabellen-Bereinigung. | deferred | Folge-Issue fuer Implementierung, Migration und Rueckbau von `--rotate-ip-salt`/Config-Pflicht anlegen; Entfernung erfolgt im gleichen Schritt wie Guardrails. |
| Doku & Tracking | Restluecken werden in ISS-010 gepflegt; ISS-005 referenziert diese Matrix. | done | Bei Statuswechseln konsistent nachziehen. |
| DoD | P1-D ist erst abgeschlossen, wenn Entscheidungen umgesetzt/nachweisbar sind. | done | Konkrete Nachweislinks pro Testlauf sammeln. |

## P1-D Testmatrix (Entwurf)
| Bereich | Ziel | Nachweis/Schritt | Status |
| --- | --- | --- | --- |
| Vertrags-Tests | Config-Regeln je Pipeline-Phase valide | `php bin/cli config lint <pipeline>` (alle relevanten Pipelines/Phasen) | open |
| Build-Tests | Preview-Build reproduzierbar mit Fixtures | `php bin/cli setup preview` + `php bin/cli build preview` | open |
| Runtime-Tests | Fachlogik mit Preview-Runtime laeuft | `composer run test` plus pipeline-relevante Feature-Pruefungen | open |
| Deploy-Artefakt | Paket ist runtime-vollstaendig | Artefakt-Checks inkl. erwarteter Dateien/Config | open |
| Pre-Deploy-Smoke | HTTP-Basischeck vor FTP-Deploy | Smoke-Schritt im Workflow vor FTP | open |
| Post-Deploy-Smoke | Zielsystem antwortet korrekt | `curl` gegen Preview-URL (`/`, `/cv`, `/contact`) | deferred |

## Akzeptanzkriterien
- Fuer alle Zeilen der Entscheidungstabelle ist ein Status gepflegt (`done`, `deferred`, `open` mit Begruendung).
- Es gibt eine verbindliche Testmatrix fuer Build/Runtime/Deploy-Smoke inkl. Nachweis-Kommandos.
- ISS-005 verweist auf diese Issue als Voraussetzung fuer P1-D-Abschluss.
- Restluecken sind mit klaren Folge-Issues oder bewusster Verschiebung dokumentiert.

## Abhaengigkeiten
- Story-Kontext:
  - [STY-001](STY-001-qualitaetsrahmen-repo-app-und-config-lib.md)
- Wirkt auf:
  - [ISS-005](ISS-005-preview-workflow-reenable-from-dev.md)
